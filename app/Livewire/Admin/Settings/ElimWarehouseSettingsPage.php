<?php

namespace App\Livewire\Admin\Settings;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Support\Elim\ElimWarehouseAddress;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Elim Warehouse Settings'])]
class ElimWarehouseSettingsPage extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $mobile = '';

    public string $address = '';

    public string $province = '';

    public string $city = '';

    public string $area = '';

    public function mount(): void
    {
        $raw = SettingHelper::get(ElimWarehouseAddress::SETTING_KEY);
        $address = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($address)) {
            return;
        }

        $this->name = (string) ($address['name'] ?? '');
        $this->phone = (string) ($address['phone'] ?? '');
        $this->mobile = (string) ($address['mobile'] ?? '');
        $this->address = (string) ($address['address'] ?? '');
        $this->province = (string) ($address['province'] ?? '');
        $this->city = (string) ($address['city'] ?? '');
        $this->area = (string) ($address['area'] ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'mobile' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'province' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'area' => ['required', 'string', 'max:120'],
        ]);

        Setting::updateOrCreate(
            ['key' => ElimWarehouseAddress::SETTING_KEY],
            ['value' => json_encode([
                'name' => $this->name,
                'phone' => $this->phone,
                'mobile' => $this->mobile,
                'address' => $this->address,
                'province' => $this->province,
                'city' => $this->city,
                'area' => $this->area,
            ], JSON_UNESCAPED_UNICODE)]
        );

        SettingHelper::clearCache();
        flash()->success('Elim warehouse address saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings.elim-warehouse-settings-page')
            ->title('Elim Warehouse Settings');
    }
}
