<?php

namespace App\Features\Idempotency\Exceptions;

use RuntimeException;

final class IdempotencyKeyConflict extends RuntimeException {}
