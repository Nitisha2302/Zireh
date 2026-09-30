<?php

namespace App\Services\Alif\Contracts;

use App\Models\User;

interface AlifAccountResolverInterface
{
    /**
     * Map the `account` string Alif sends to a customer who may be topped up.
     *
     * Returns null when no customer matches or the customer cannot receive
     * funds, which the caller answers with protocol code 404.
     */
    public function resolve(string $account): ?User;
}
