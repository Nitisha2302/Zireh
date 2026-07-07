<?php

namespace App\Livewire\Admin\Settings;

use App\Services\Admin\WarehouseLoginAccountService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'China Warehouse Login'])]
class ChinaWarehouseLoginSettingsPage extends Component
{
    public string $login_username = '';

    public string $login_email = '';

    public string $login_password = '';

    public string $login_password_confirmation = '';

    public function mount(WarehouseLoginAccountService $loginAccounts): void
    {
        $account = $loginAccounts->findChinaAccount();

        if ($account) {
            $this->login_username = $account->username;
            $this->login_email = $account->email;
        }
    }

    public function save(WarehouseLoginAccountService $loginAccounts): void
    {
        try {
            $loginAccounts->syncChinaAccount([
                'login_username' => $this->login_username,
                'login_email' => $this->login_email,
                'login_password' => $this->login_password,
                'login_password_confirmation' => $this->login_password_confirmation,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        $this->reset(['login_password', 'login_password_confirmation']);

        flash()->success(__('admin.china_warehouse_login_saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.china-warehouse-login-settings-page')
            ->title(__('admin.china_warehouse_login_settings'));
    }
}
