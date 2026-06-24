<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profilePhoto = app(FileManager::class)->url($this->profile_photo);

        return [
            'id' => $this->id,
            'full_name' => $this->name,
            'phone_number' => $this->phone,
            'email' => $this->email,
            'profile_photo' => $profilePhoto,
            'preferred_language' => $this->preferred_language,
            'device_token' => $this->device_token,
            'location_permission' => (bool) $this->location_permission,
            'referral_code' => $this->referral_code,
            'referred_by_code' => $this->referred_by_code,
            'phone_verified_at' => $this->phone_verified_at,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
        ];
    }
}
