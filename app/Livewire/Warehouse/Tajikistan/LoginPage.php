<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app', ['title' => 'Tajikistan Warehouse Login'])]
class LoginPage extends Component
{
    #[Validate('required|string|max:255')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            return;
        }

        if ($admin->isTajikistanWarehouseStaff()) {
            $this->redirectRoute('tajikistan.orders.index', navigate: true);
        }
    }

    public function authenticate()
    {
        $login = trim($this->login);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $admin = Admin::query()->where($field, $login)->first();

        if (! Auth::guard('admin')->attempt([$field => $login, 'password' => $this->password], $this->remember)) {
            LoginLog::recordFailure('tajikistan_warehouse', $login, request(), $admin, 'Invalid credentials');
            $this->addError('login', __('admin.invalid_credentials'));

            return null;
        }

        $admin = Auth::guard('admin')->user();

        if ($admin->isSuperAdmin()) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.use_admin_login'));

            return null;
        }

        if ($admin->isChinaWarehouseStaff()) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.use_china_warehouse_login'));

            return null;
        }

        if (! $admin->isTajikistanWarehouseStaff()) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.invalid_credentials'));

            return null;
        }

        if ($admin->warehouse_id === null) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.warehouse_staff_missing_assignment'));

            return null;
        }

        $admin->load('warehouse');

        if (! $admin->warehouse || ! $admin->warehouse->isActive()) {
            Auth::guard('admin')->logout();
            $this->addError('login', __('admin.warehouse_login_inactive'));

            return null;
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        LoginLog::recordSuccess($admin, 'tajikistan_warehouse', $login, request());

        Log::info('Tajikistan warehouse staff logged in.', [
            'admin_id' => $admin->id,
            'warehouse_id' => $admin->warehouse_id,
        ]);

        flash()->success(__('admin.welcome_back', ['name' => $admin->name]));

        return $this->redirectRoute('tajikistan.orders.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouse.tajikistan.login-page')
            ->title(__('admin.tajikistan_warehouse_login'));
    }
}
