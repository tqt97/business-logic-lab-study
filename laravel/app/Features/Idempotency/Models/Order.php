<?php

namespace App\Features\Idempotency\Models;

use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Enums\OrderStatus;
use App\Models\User;
use Database\Factories\Idempotency\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'product_code', 'quantity', 'total_minor', 'currency', 'status'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $table = 'idempotency_orders';

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function idempotencyRecord(): HasOne
    {
        return $this->hasOne(IdempotencyRecord::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'product_code' => OrderProduct::class,
            'quantity' => 'integer',
            'total_minor' => 'integer',
            'status' => OrderStatus::class,
        ];
    }
}
