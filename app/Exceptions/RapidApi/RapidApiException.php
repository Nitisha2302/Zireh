<?php

namespace App\Exceptions\RapidApi;

use RuntimeException;
use Throwable;

class RapidApiException extends RuntimeException
{
    public function __construct(
        string $message = 'RapidAPI request failed.',
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
