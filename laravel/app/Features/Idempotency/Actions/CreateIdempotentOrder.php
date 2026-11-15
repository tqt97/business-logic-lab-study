<?php

namespace App\Features\Idempotency\Actions;

use App\Features\Idempotency\Domain\IdempotentOrderResult;
use App\Features\Idempotency\Enums\IdempotencyRecordStatus;
use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Enums\OrderStatus;
use App\Features\Idempotency\Exceptions\IdempotencyKeyConflict;
use App\Features\Idempotency\Exceptions\IdempotencyRequestInProgress;
use App\Features\Idempotency\Models\IdempotencyRecord;
use App\Features\Idempotency\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CreateIdempotentOrder
{
    private const OPERATION = 'orders.create.v1';

    private const RETENTION_HOURS = 24;

    /**
     * @param  array{product_code: string, quantity: int}  $input
     */
    public function handle(User $user, string $idempotencyKey, array $input): IdempotentOrderResult
    {
        $product = OrderProduct::from($input['product_code']);
        $quantity = $input['quantity'];
        $requestHash = hash('sha256', json_encode([
            'product_code' => $product->value,
            'quantity' => $quantity,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $keyHash = hash('sha256', $idempotencyKey);

        try {
            return DB::transaction(function () use ($user, $product, $quantity, $requestHash, $keyHash): IdempotentOrderResult {
                $record = IdempotencyRecord::query()
                    ->where('user_id', $user->getKey())
                    ->where('operation', self::OPERATION)
                    ->where('key_hash', $keyHash)
                    ->lockForUpdate()
                    ->first();
                $now = CarbonImmutable::now('UTC');

                if ($record !== null && $record->expires_at->greaterThan($now)) {
                    return $this->resolveUnexpiredRecord($record, $requestHash);
                }

                if ($record === null) {
                    $record = IdempotencyRecord::query()->create([
                        'user_id' => $user->getKey(),
                        'operation' => self::OPERATION,
                        'key_hash' => $keyHash,
                        'request_hash' => $requestHash,
                        'status' => IdempotencyRecordStatus::Processing,
                        'expires_at' => $now->addHours(self::RETENTION_HOURS),
                    ]);
                } else {
                    $record->forceFill([
                        'request_hash' => $requestHash,
                        'status' => IdempotencyRecordStatus::Processing,
                        'order_id' => null,
                        'response_code' => null,
                        'response_body' => null,
                        'expires_at' => $now->addHours(self::RETENTION_HOURS),
                    ])->saveOrFail();
                }

                $order = Order::query()->create([
                    'user_id' => $user->getKey(),
                    'product_code' => $product,
                    'quantity' => $quantity,
                    'total_minor' => $product->unitPriceMinor() * $quantity,
                    'currency' => 'USD',
                    'status' => OrderStatus::Created,
                ]);
                $body = $this->responseBody($order);

                $record->forceFill([
                    'order_id' => $order->getKey(),
                    'response_code' => 201,
                    'response_body' => $body,
                    'status' => IdempotencyRecordStatus::Completed,
                    'expires_at' => CarbonImmutable::now('UTC')->addHours(self::RETENTION_HOURS),
                ])->saveOrFail();

                return new IdempotentOrderResult($body, 201, false);
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $record = IdempotencyRecord::query()
                ->where('user_id', $user->getKey())
                ->where('operation', self::OPERATION)
                ->where('key_hash', $keyHash)
                ->first();

            if ($record === null) {
                throw $exception;
            }

            if ($record->expires_at->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
                // The collision is only recoverable while the winning claim is still valid.
                // Do not recurse here: repeated expiry races could otherwise retry forever.
                throw $exception;
            }

            return $this->resolveUnexpiredRecord($record, $requestHash);
        }
    }

    private function resolveUnexpiredRecord(IdempotencyRecord $record, string $requestHash): IdempotentOrderResult
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            throw new IdempotencyKeyConflict;
        }

        if (
            $record->status !== IdempotencyRecordStatus::Completed
            || $record->order_id === null
            || $record->response_code === null
            || $record->response_body === null
        ) {
            throw new IdempotencyRequestInProgress;
        }

        return new IdempotentOrderResult($record->response_body, $record->response_code, true);
    }

    /** @return array{data: array{id: int, product_code: string, quantity: int, total_minor: int, currency: string, status: string}, response_version: int} */
    private function responseBody(Order $order): array
    {
        return [
            'data' => [
                'id' => (int) $order->getKey(),
                'product_code' => $order->product_code->value,
                'quantity' => $order->quantity,
                'total_minor' => $order->total_minor,
                'currency' => $order->currency,
                'status' => $order->status->value,
            ],
            'response_version' => 1,
        ];
    }
}
