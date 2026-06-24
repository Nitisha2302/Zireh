<?php

namespace App\Livewire\Admin\Settings;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Didit Settings'])]
class DiditSettingsPage extends Component
{
    public string $didit_base_url = '';
    public string $didit_api_key = '';
    public string $didit_workflow_id = '';
    public string $didit_callback_url = '';

    public function mount(): void
    {
        $this->didit_base_url = (string) SettingHelper::get('didit_base_url', config('services.didit.base_url'));
        $this->didit_api_key = (string) SettingHelper::get('didit_api_key', config('services.didit.api_key', ''));
        $this->didit_workflow_id = (string) SettingHelper::get('didit_workflow_id', config('services.didit.workflow_id', ''));
        $this->didit_callback_url = (string) SettingHelper::get('didit_callback_url', config('services.didit.callback_url', ''));
    }

    public function save(): void
    {
        $this->validate([
            'didit_base_url' => ['required', 'url'],
            'didit_api_key' => ['required', 'string'],
            'didit_workflow_id' => ['required', 'string'],
            'didit_callback_url' => ['required', 'url'],
        ]);

        foreach (
            [
                'didit_base_url' => $this->didit_base_url,
                'didit_api_key' => $this->didit_api_key,
                'didit_workflow_id' => $this->didit_workflow_id,
                'didit_callback_url' => $this->didit_callback_url,
            ] as $key => $value
        ) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        SettingHelper::clearCache();
    }

    public function render()
    {
        return view('livewire.admin.settings.didit-settings-page')
            ->title(__('admin.didit_settings'));
    }
}
