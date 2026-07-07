<?php

namespace App\Livewire\Warehouse\Concerns;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

trait ManagesWarehouseProfile
{
    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $admin = Auth::guard('admin')->user();

        $this->validate([
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        if (! Hash::check($this->currentPassword, $admin->password)) {
            $this->addError('currentPassword', __('admin.current_password_incorrect'));

            return;
        }

        $admin->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['currentPassword', 'password', 'password_confirmation']);

        flash()->success(__('admin.password_updated'));
    }

    protected function renderProfile(string $panelTitle)
    {
        return view('livewire.warehouse.profile-page', [
            'admin' => Auth::guard('admin')->user()?->load('warehouse'),
            'roleLabel' => Admin::roles()[Auth::guard('admin')->user()?->role] ?? '',
            'panelTitle' => $panelTitle,
        ])->title(__('admin.my_profile'));
    }
}
