<?php

namespace App\Features\Idempotency\Exceptions;

use RuntimeException;

final class IdempotencyRequestInProgress extends RuntimeException {}
