<?php

namespace Database\Factories\Idempotency;

use App\Features\Idempotency\Enums\IdempotencyRecordStatus;
use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Enums\OrderStatus;
use App\Features\Idempotency\Models\IdempotencyRecord;
use App\Features\Idempotency\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<IdempotencyRecord> */
class IdempotencyRecordFactory extends Factory
{
    protected $model = IdempotencyRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation' => 'orders.create.v1',
            'key_hash' => hash('sha256', Str::uuid()->toString()),
            'request_hash' => hash('sha256', Str::uuid()->toString()),
            'status' => IdempotencyRecordStatus::Processing,
            'order_id' => null,
            'response_code' => null,
            'response_body' => null,
            'expires_at' => CarbonImmutable::now('UTC')->addDay(),
        ];
    }

    public function completed(): static
    {
        return $this->afterCreating(function (IdempotencyRecord $record): void {
            $order = Order::query()->create([
                'user_id' => $record->user_id,
                'product_code' => OrderProduct::StarterKit,
                'quantity' => 1,
                'total_minor' => OrderProduct::StarterKit->unitPriceMinor(),
                'currency' => 'USD',
                'status' => OrderStatus::Created,
            ]);

            $record->forceFill([
                'request_hash' => hash('sha256', json_encode([
                    'product_code' => OrderProduct::StarterKit->value,
                    'quantity' => 1,
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
                'status' => IdempotencyRecordStatus::Completed,
                'order_id' => $order->getKey(),
                'response_code' => 201,
                'response_body' => [
                    'data' => [
                        'id' => (int) $order->getKey(),
                        'product_code' => $order->product_code->value,
                        'quantity' => $order->quantity,
                        'total_minor' => $order->total_minor,
                        'currency' => $order->currency,
                        'status' => $order->status->value,
                    ],
                    'response_version' => 1,
                ],
            ])->saveOrFail();
        });
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => CarbonImmutable::now('UTC')->subSecond()]);
    }
}
