<?php

namespace App\Exceptions\Elim;

use RuntimeException;
use Throwable;

class ElimException extends RuntimeException
{
    public function __construct(
        string $message = 'ELIM API request failed.',
        int $code = 0,
        Throwable|null $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }
}
