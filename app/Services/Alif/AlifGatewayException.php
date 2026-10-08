<?php

namespace App\Services\Alif;

use RuntimeException;
use Throwable;

class AlifGatewayException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|string  $body
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly array|string $body,
        public readonly string $rawBody = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus > 0 ? $httpStatus : 0, $previous);
    }

    public function httpStatusForLog(): int
    {
        return $this->httpStatus > 0 ? $this->httpStatus : 502;
    }

    public function responseCodeForLog(): int
    {
        if (is_array($this->body) && isset($this->body['code']) && is_numeric($this->body['code'])) {
            return (int) $this->body['code'];
        }

        return $this->httpStatusForLog();
    }
}
