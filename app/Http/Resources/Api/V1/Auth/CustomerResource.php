<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->name,
            'phone_number' => $this->phone,
            'email' => $this->email,
            'profile_photo' => app(FileManager::class)->url($this->profile_photo),
            'status' => $this->status,
            'preferred_language' => $this->preferred_language,
            'device_token' => $this->device_token,
            'phone_verified_at' => $this->phone_verified_at,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
