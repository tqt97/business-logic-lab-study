<?php

namespace App\Features\Idempotency\Domain;

final readonly class IdempotentOrderResult
{
    /**
     * @param  array{data: array{id: int, product_code: string, quantity: int, total_minor: int, currency: string, status: string}, response_version: int}  $body
     */
    public function __construct(
        public array $body,
        public int $responseCode,
        public bool $replayed,
    ) {}
}
