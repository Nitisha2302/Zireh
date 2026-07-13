<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class LegalContentController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->successResponse([
            'privacy_policy' => (string) SettingHelper::get('privacy_policy', ''),
            'terms_conditions' => (string) SettingHelper::get('terms_conditions', ''),
            'delete_account' => (string) SettingHelper::get('delete_account', ''),
        ], __('api.legal_content_fetched'));
    }
}
