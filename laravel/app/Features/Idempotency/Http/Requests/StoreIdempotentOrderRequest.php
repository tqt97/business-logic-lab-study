<?php

namespace App\Features\Idempotency\Http\Requests;

use App\Features\Idempotency\Enums\OrderProduct;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreIdempotentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $key = $this->header('Idempotency-Key');

        $this->merge([
            'idempotency_key' => is_string($key) ? trim($key) : null,
        ]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
            'product_code' => ['required', new Enum(OrderProduct::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'idempotency_key.required' => __('idempotency.validation.key_required'),
            'idempotency_key.string' => __('idempotency.validation.key_invalid'),
            'idempotency_key.max' => __('idempotency.validation.key_invalid'),
            'product_code.required' => __('idempotency.validation.product'),
            'product_code.enum' => __('idempotency.validation.product'),
            'quantity.required' => __('idempotency.validation.quantity'),
            'quantity.integer' => __('idempotency.validation.quantity'),
            'quantity.min' => __('idempotency.validation.quantity'),
            'quantity.max' => __('idempotency.validation.quantity'),
        ];
    }
}
