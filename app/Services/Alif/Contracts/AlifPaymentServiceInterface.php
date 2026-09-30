<?php

namespace App\Services\Alif\Contracts;

use App\Support\Alif\AlifResult;

interface AlifPaymentServiceInterface
{
    /**
     * Handle one decoded Alif request body and produce the protocol answer.
     *
     * Never throws: every failure is mapped onto a documented response code.
     */
    public function handle(array $payload): AlifResult;
}
