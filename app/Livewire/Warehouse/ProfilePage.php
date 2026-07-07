<?php

namespace App\Livewire\Warehouse;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::warehouse', ['title' => 'Profile', 'panel' => 'profile'])]
class ProfilePage extends Component
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

    public function render()
    {
        return view('livewire.warehouse.profile-page', [
            'admin' => Auth::guard('admin')->user()?->load('warehouse'),
            'roleLabel' => Admin::roles()[Auth::guard('admin')->user()?->role] ?? '',
        ])->title(__('admin.my_profile'));
    }
}
