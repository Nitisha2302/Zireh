<?php

namespace App\Livewire\Admin\Settings;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Company Settings'])]
class CompanySettingsPage extends Component
{
    use WithFileUploads;

    public string $company_name = '';

    public $company_logo = null;

    public ?string $existingLogo = null;

    public ?string $existingLogoUrl = null;

    public function mount(FileManager $fileManager): void
    {
        $company = SettingHelper::company();
        $this->company_name = $company['name'];
        $this->existingLogo = $company['logo'];
        $this->existingLogoUrl = $company['logo_url'];
    }

    public function save(FileManager $fileManager): void
    {
        $this->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'company_name'],
            ['value' => trim($this->company_name)]
        );

        if ($this->company_logo) {
            $path = $fileManager->store($this->company_logo, 'settings/company');

            if ($path) {
                if ($this->existingLogo && $this->existingLogo !== $path) {
                    $fileManager->delete($this->existingLogo);
                }

                Setting::updateOrCreate(
                    ['key' => 'company_logo'],
                    ['value' => $path]
                );

                $this->existingLogo = $path;
                $this->existingLogoUrl = $fileManager->url($path);
                $this->company_logo = null;
            }
        }

        SettingHelper::clearCache();

        $company = SettingHelper::company();
        $this->company_name = $company['name'];
        $this->existingLogo = $company['logo'];
        $this->existingLogoUrl = $company['logo_url'];

        session()->flash('success', __('admin.company_settings_saved_successfully'));
    }

    public function removeLogo(FileManager $fileManager): void
    {
        if ($this->existingLogo) {
            $fileManager->delete($this->existingLogo);
        }

        Setting::query()->where('key', 'company_logo')->delete();
        SettingHelper::clearCache();

        $this->existingLogo = null;
        $this->existingLogoUrl = null;
        $this->company_logo = null;

        session()->flash('success', __('admin.company_logo_removed_successfully'));
    }

    public function render()
    {
        return view('livewire.admin.settings.company-settings-page')
            ->title(__('admin.company_settings'));
    }
}
