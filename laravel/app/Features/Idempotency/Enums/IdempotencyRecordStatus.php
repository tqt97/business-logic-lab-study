<?php

namespace App\Features\Idempotency\Enums;

enum IdempotencyRecordStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
}
