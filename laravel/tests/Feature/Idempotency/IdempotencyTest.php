<?php

use App\Features\Idempotency\Enums\OrderProduct;
use App\Features\Idempotency\Models\IdempotencyRecord;
use App\Features\Idempotency\Models\Order;
use App\Models\User;

test('authenticated order request replays the committed response for the same key and payload', function () {
    $user = User::factory()->create();
    $payload = ['product_code' => OrderProduct::StarterKit->value, 'quantity' => 2];

    $first = $this->actingAs($user)->postJson(route('labs.idempotency.orders.store'), $payload, [
        'Idempotency-Key' => 'retry-key-1',
    ]);
    $replay = $this->postJson(route('labs.idempotency.orders.store'), $payload, [
        'Idempotency-Key' => 'retry-key-1',
    ]);

    $first->assertCreated()->assertHeader('Idempotency-Replayed', 'false');
    $replay->assertCreated()->assertHeader('Idempotency-Replayed', 'true')->assertExactJson($first->json());
    expect(Order::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(IdempotencyRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('same key with a different payload conflicts without creating another order', function () {
    $user = User::factory()->create();
    $headers = ['Idempotency-Key' => 'retry-key-2'];
    $this->actingAs($user)->postJson(route('labs.idempotency.orders.store'), [
        'product_code' => OrderProduct::StarterKit->value,
        'quantity' => 1,
    ], $headers)->assertCreated();

    $this->postJson(route('labs.idempotency.orders.store'), [
        'product_code' => OrderProduct::CoursePass->value,
        'quantity' => 1,
    ], $headers)->assertConflict()->assertJsonPath('error', 'idempotency_key_conflict');

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('idempotency results are isolated by authenticated user and client price fields are ignored', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $payload = ['product_code' => OrderProduct::StarterKit->value, 'quantity' => 1];

    $this->actingAs($firstUser)->postJson(route('labs.idempotency.orders.store'), $payload, [
        'Idempotency-Key' => 'shared-key',
    ])->assertCreated();

    $this->actingAs($secondUser)->postJson(route('labs.idempotency.orders.store'), $payload, [
        'Idempotency-Key' => 'shared-key',
    ])->assertCreated()->assertHeader('Idempotency-Replayed', 'false');

    expect(Order::query()->count())->toBe(2)
        ->and(Order::query()->where('user_id', $firstUser->id)->value('total_minor'))->toBe(OrderProduct::StarterKit->unitPriceMinor());

    $this->actingAs($firstUser)->postJson(route('labs.idempotency.orders.store'), $payload + ['total_minor' => 1], [
        'Idempotency-Key' => 'price-tamper',
    ])->assertCreated()->assertJsonPath('data.total_minor', OrderProduct::StarterKit->unitPriceMinor());
});

test('expired idempotency key can create a new order and validation protects input bounds', function () {
    $user = User::factory()->create();
    $payload = ['product_code' => OrderProduct::StarterKit->value, 'quantity' => 1];
    $headers = ['Idempotency-Key' => 'expired-key'];

    $this->actingAs($user)->postJson(route('labs.idempotency.orders.store'), $payload, $headers)->assertCreated();
    IdempotencyRecord::query()->firstOrFail()->forceFill(['expires_at' => now()->subSecond()])->saveOrFail();

    $this->postJson(route('labs.idempotency.orders.store'), $payload, $headers)
        ->assertCreated()->assertHeader('Idempotency-Replayed', 'false');
    $this->postJson(route('labs.idempotency.orders.store'), [
        'product_code' => 'unknown',
        'quantity' => 0,
    ], ['Idempotency-Key' => 'invalid-key'])->assertUnprocessable();

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(2);
});

test('order endpoint requires authentication and rate limits mutations', function () {
    $url = route('labs.idempotency.orders.store');
    $this->postJson($url, [
        'product_code' => OrderProduct::StarterKit->value,
        'quantity' => 1,
    ], ['Idempotency-Key' => 'anonymous-key'])->assertUnauthorized();

    $this->actingAs(User::factory()->create());
    for ($attempt = 0; $attempt < 20; $attempt++) {
        $this->postJson($url, [
            'product_code' => OrderProduct::StarterKit->value,
            'quantity' => 1,
        ], ['Idempotency-Key' => 'throttle-'.$attempt])->assertCreated();
    }

    $this->postJson($url, [
        'product_code' => OrderProduct::StarterKit->value,
        'quantity' => 1,
    ], ['Idempotency-Key' => 'throttle-overflow'])->assertTooManyRequests();
});
