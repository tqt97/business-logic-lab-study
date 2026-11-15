<?php

namespace App\Features\Idempotency\Http\Controllers;

use App\Features\Idempotency\Actions\CreateIdempotentOrder;
use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Exceptions\IdempotencyKeyConflict;
use App\Features\Idempotency\Exceptions\IdempotencyRequestInProgress;
use App\Features\Idempotency\Http\Requests\StoreIdempotentOrderRequest;
use App\Features\Idempotency\Models\Order;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IdempotencyController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        $orders = Order::query()
            ->where('user_id', $user->getKey())
            ->latest()
            ->limit(10)
            ->get();
        $products = OrderProduct::cases();
        $idempotencyKey = Str::uuid()->toString();

        return view('labs.idempotency.index', compact('orders', 'products', 'idempotencyKey'));
    }

    public function store(StoreIdempotentOrderRequest $request, CreateIdempotentOrder $createOrder): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $result = $createOrder->handle(
                user: $user,
                idempotencyKey: $request->validated('idempotency_key'),
                input: [
                    'product_code' => $request->validated('product_code'),
                    'quantity' => (int) $request->validated('quantity'),
                ],
            );
        } catch (IdempotencyKeyConflict) {
            return response()->json([
                'error' => 'idempotency_key_conflict',
                'message' => __('idempotency.errors.key_conflict'),
            ], 409);
        } catch (IdempotencyRequestInProgress) {
            return response()->json([
                'error' => 'idempotency_request_in_progress',
                'message' => __('idempotency.errors.in_progress'),
            ], 409, ['Retry-After' => '1']);
        }

        return response()->json($result->body, $result->responseCode, [
            'Idempotency-Replayed' => $result->replayed ? 'true' : 'false',
        ]);
    }
}
