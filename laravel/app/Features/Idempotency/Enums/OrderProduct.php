<?php

namespace App\Features\Idempotency\Enums;

enum OrderProduct: string
{
    case StarterKit = 'starter-kit';
    case CoursePass = 'course-pass';

    public function unitPriceMinor(): int
    {
        return match ($this) {
            self::StarterKit => 1299,
            self::CoursePass => 12900,
        };
    }
}
