<?php

namespace Database\Factories\Idempotency;

use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Enums\OrderStatus;
use App\Features\Idempotency\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_code' => OrderProduct::StarterKit,
            'quantity' => 1,
            'total_minor' => OrderProduct::StarterKit->unitPriceMinor(),
            'currency' => 'USD',
            'status' => OrderStatus::Created,
        ];
    }
}
