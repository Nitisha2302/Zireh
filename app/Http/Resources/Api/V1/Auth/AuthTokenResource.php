<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer' => new CustomerResource($this['user']),
            'token' => $this['token'],
            'token_type' => 'Bearer',
        ];
    }
}
