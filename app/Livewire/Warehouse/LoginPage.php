<?php

namespace App\Livewire\Warehouse;

use App\Models\Admin;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app', ['title' => 'Warehouse Login'])]
class LoginPage extends Component
{
    #[Validate('required|string|max:255')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (! Auth::guard('admin')->check()) {
            return;
        }

        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin && $admin->isWarehouseStaff()) {
            $this->redirectRoute($admin->warehouseHomeRoute(), navigate: true);
        }
    }

    public function authenticate()
    {
        $login = trim($this->login);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $admin = Admin::query()->where($field, $login)->first();

        if (! Auth::guard('admin')->attempt([$field => $login, 'password' => $this->password], $this->remember)) {
            LoginLog::recordFailure('warehouse', $login, request(), $admin, 'Invalid credentials');

            $this->addError('login', __('admin.invalid_credentials'));

            return null;
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin || ! $admin->isWarehouseStaff()) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.use_admin_login'));

            return null;
        }

        if ($admin->isTajikistanWarehouseStaff() && $admin->warehouse_id === null) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.warehouse_staff_missing_assignment'));

            return null;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        LoginLog::recordSuccess($admin, 'warehouse', $login, request());

        Log::info('Warehouse staff logged in.', [
            'admin_id' => $admin->id,
            'role' => $admin->role,
            'warehouse_id' => $admin->warehouse_id,
        ]);

        flash()->success(__('admin.welcome_back', ['name' => $admin->name]));

        return $this->redirectRoute($admin->warehouseHomeRoute(), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouse.login-page')
            ->title(__('admin.warehouse_login'));
    }
}
