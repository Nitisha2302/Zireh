<?php

namespace App\Exceptions;

use Exception;

class ExchangeRateFetchException extends Exception
{
    public function __construct(
        string $message = 'Unable to fetch exchange rate.',
        public readonly ?array $context = null,
    ) {
        parent::__construct($message);
    }
}
