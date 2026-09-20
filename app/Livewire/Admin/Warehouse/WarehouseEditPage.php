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

#[Layout('layouts::admin', ['title' => 'Edit Warehouse'])]
class WarehouseEditPage extends Component
{
    use ManagesWarehouseWorkingHours;

    public Warehouse $warehouse;

    public string $warehouse_name = '';

    public string $contact_number = '';

    public string $login_username = '';

    public string $login_email = '';

    public string $login_password = '';

    public string $login_password_confirmation = '';

    public string $address = '';

    public string $status = Warehouse::STATUS_ACTIVE;

    public function mount(Warehouse $warehouse, WarehouseLoginAccountService $loginAccounts): void
    {
        $this->warehouse = $warehouse;
        $this->warehouse_name = $warehouse->warehouse_name;
        $this->contact_number = $warehouse->contact_number ?? '';
        $this->address = $warehouse->address;
        $this->status = $warehouse->status;
        $this->fillWorkingHoursFromWarehouse($warehouse);

        $account = $loginAccounts->findTajikistanAccount($warehouse);

        if ($account) {
            $this->login_username = $account->username;
            $this->login_email = $account->email;
        }
    }

    protected function rules(): array
    {
        $account = app(WarehouseLoginAccountService::class)->findTajikistanAccount($this->warehouse);

        return array_merge(
            $this->warehouseRules(),
            $this->loginRules(isCreate: false, ignoreAdminId: $account?->id),
            $this->workingHoursRules(),
        );
    }

    public function update(WarehouseLoginAccountService $loginAccounts): void
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

        DB::transaction(function () use ($data, $loginAccounts): void {
            $this->warehouse->update($data);
            $this->syncWorkingHours($this->warehouse);

            $loginAccounts->syncTajikistanAccount($this->warehouse->fresh(), [
                'login_username' => $this->login_username,
                'login_email' => $this->login_email,
                'login_password' => $this->login_password,
                'login_password_confirmation' => $this->login_password_confirmation,
            ], isCreate: false);
        });

        flash()->success(__('admin.warehouse_updated'));
        $this->redirectRoute('admin.warehouses.show', $this->warehouse);
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-edit-page', [
            'statuses' => Warehouse::statuses(),
            'isEdit' => true,
        ])->title(__('admin.edit_warehouse'));
    }

    protected function warehouseRules(): array
    {
        return [
            'warehouse_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
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
            'contact_number' => $validated['contact_number'],
            'address' => $validated['address'],
            'status' => $validated['status'],
        ];
    }
}
