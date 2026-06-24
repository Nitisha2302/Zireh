<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;

class PublicController extends Controller
{
    public function privacyPolicy()
    {
        $privacyPolicy = (string) SettingHelper::get('privacy_policy', 'Privacy policy not available');
        
        return view('public.privacy-policy', [
            'content' => $privacyPolicy,
        ]);
    }

    public function termsConditions()
    {
        $termsConditions = (string) SettingHelper::get('terms_conditions', 'Terms and conditions not available');
        
        return view('public.terms-conditions', [
            'content' => $termsConditions,
        ]);
    }

    public function deleteAccount()
    {
        $deleteAccountInfo = (string) SettingHelper::get('delete_account', 'Account deletion information not available');
        
        return view('public.delete-account', [
            'content' => $deleteAccountInfo,
        ]);
    }
}
