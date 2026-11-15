<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('product_code', 32);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total_minor');
            $table->string('currency', 3)->default('USD');
            $table->string('status', 16)->default('created');
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'idem_orders_user_created_idx');
        });

        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('operation', 64);
            $table->char('key_hash', 64);
            $table->char('request_hash', 64);
            $table->string('status', 16)->default('processing');
            $table->foreignId('order_id')->nullable()->constrained('idempotency_orders')->restrictOnDelete();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['user_id', 'operation', 'key_hash'], 'idem_scope_key_unique');
            $table->unique('order_id', 'idem_order_link_unique');
            $table->index('expires_at', 'idem_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('idempotency_orders');
    }
};
