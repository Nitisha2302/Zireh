<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;
use App\Models\UserWishlistItem;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', Rule::in([
                UserWishlistItem::PLATFORM_TAOBAO,
                UserWishlistItem::PLATFORM_1688,
            ])],
            'product_id' => ['required', 'string', 'max:255'],
            'lang' => ['nullable', 'string', 'max:10'],
        ];
    }
}
