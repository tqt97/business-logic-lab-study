<?php

namespace App\Features\Idempotency\Enums;

enum OrderStatus: string
{
    case Created = 'created';
}
