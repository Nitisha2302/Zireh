<?php

namespace App\Livewire\Admin\Warehouse;

use App\Livewire\Admin\Warehouse\Concerns\ManagesWarehouseWorkingHours;
use App\Models\Warehouse;
use App\Services\Admin\WarehouseLoginAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Warehouse'])]
class WarehouseCreatePage extends Component
{
    use ManagesWarehouseWorkingHours;

    public string $warehouse_name = '';

    public string $email = '';

    public string $login_username = '';

    public string $login_email = '';

    public string $login_password = '';

    public string $login_password_confirmation = '';

    public string $address = '';

    public string $status = Warehouse::STATUS_ACTIVE;

    protected function rules(): array
    {
        return array_merge($this->warehouseRules(), $this->loginRules(isCreate: true), $this->workingHoursRules());
    }

    public function save(WarehouseLoginAccountService $loginAccounts): void
    {
        try {
            $this->normalizeWorkingHourTimes();
            $validated = $this->withValidator(function ($validator): void {
                $validator->after(fn ($validator) => $this->validateWorkingHoursConsistency($validator));
            })->validate();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        $data = $this->mapValidated($validated);
        $data['warehouse_code'] = Warehouse::generateUniqueCode();

        DB::transaction(function () use ($data, $loginAccounts): void {
            $warehouse = Warehouse::create($data);
            $this->syncWorkingHours($warehouse);

            $loginAccounts->syncTajikistanAccount($warehouse, [
                'login_username' => $this->login_username,
                'login_email' => $this->login_email,
                'login_password' => $this->login_password,
                'login_password_confirmation' => $this->login_password_confirmation,
            ], isCreate: true);
        });

        flash()->success(__('admin.warehouse_created'));
        $this->redirectRoute('admin.warehouses.index');
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-create-page', [
            'statuses' => Warehouse::statuses(),
            'isEdit' => false,
        ])->title(__('admin.add_warehouse'));
    }

    protected function warehouseRules(): array
    {
        return [
            'warehouse_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::in([Warehouse::STATUS_ACTIVE, Warehouse::STATUS_INACTIVE])],
        ];
    }

    protected function loginRules(bool $isCreate, ?int $ignoreAdminId = null): array
    {
        return [
            'login_username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($ignoreAdminId)],
            'login_email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($ignoreAdminId)],
            'login_password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function mapValidated(array $validated): array
    {
        return [
            'warehouse_name' => $validated['warehouse_name'],
            'email' => $validated['email'] ?: null,
            'address' => $validated['address'],
            'status' => $validated['status'],
        ];
    }
}
