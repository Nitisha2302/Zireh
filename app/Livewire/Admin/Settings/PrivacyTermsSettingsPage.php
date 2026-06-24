<?php

namespace App\Livewire\Admin\Settings;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Privacy & Terms Settings'])]
class PrivacyTermsSettingsPage extends Component
{
    public string $privacy_policy = '';
    public string $terms_conditions = '';
    public string $delete_account = '';

    public function mount(): void
    {
        $this->privacy_policy = (string) SettingHelper::get('privacy_policy', '');
        $this->terms_conditions = (string) SettingHelper::get('terms_conditions', '');
        $this->delete_account = (string) SettingHelper::get('delete_account', '');
    }

    public function save(): void
    {
        $this->validate([
            'privacy_policy' => ['required', 'string', 'min:10'],
            'terms_conditions' => ['required', 'string', 'min:10'],
            'delete_account' => ['required', 'string', 'min:10'],
        ]);

        foreach ([
            'privacy_policy' => $this->privacy_policy,
            'terms_conditions' => $this->terms_conditions,
            'delete_account' => $this->delete_account,
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        SettingHelper::clearCache();

        session()->flash('success', __('admin.privacy_terms_saved_successfully'));
    }

    public function render()
    {
        return view('livewire.admin.settings.privacy-terms-settings-page')
            ->title(__('admin.privacy_terms_settings'));
    }
}

