<?php

namespace App\Features\Idempotency\Models;

use App\Features\Idempotency\Enums\IdempotencyRecordStatus;
use App\Models\User;
use Database\Factories\Idempotency\IdempotencyRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'operation', 'key_hash', 'request_hash', 'status', 'order_id', 'response_code', 'response_body', 'expires_at'])]
class IdempotencyRecord extends Model
{
    /** @use HasFactory<IdempotencyRecordFactory> */
    use HasFactory;

    protected $table = 'idempotency_records';

    protected static function newFactory(): IdempotencyRecordFactory
    {
        return IdempotencyRecordFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return [
            'status' => IdempotencyRecordStatus::class,
            'response_code' => 'integer',
            'response_body' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
