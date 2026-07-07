<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\Warehouse;
use App\Services\Admin\WarehouseLoginAccountService;
use App\Services\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Add Warehouse'])]
class WarehouseCreatePage extends Component
{
    use WithFileUploads;

    public string $warehouse_name = '';

    public string $warehouse_code = '';

    public mixed $image = null;

    public string $contact_person = '';

    public string $contact_number = '';

    public string $email = '';

    public string $login_username = '';

    public string $login_email = '';

    public string $login_password = '';

    public string $login_password_confirmation = '';

    public string $country = Warehouse::DEFAULT_COUNTRY;

    public string $state = '';

    public string $city = '';

    public string $address = '';

    public string $postal_code = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $status = Warehouse::STATUS_ACTIVE;

    public string $notes = '';

    protected function rules(): array
    {
        return array_merge($this->warehouseRules(), $this->loginRules(isCreate: true));
    }

    public function save(FileManager $fileManager, WarehouseLoginAccountService $loginAccounts): void
    {
        try {
            $validated = $this->validate();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        $data = $this->mapValidated($validated);

        if ($this->image) {
            $data['image'] = $fileManager->store($this->image, 'warehouses');
        }

        DB::transaction(function () use ($data, $loginAccounts): void {
            $warehouse = Warehouse::create($data);

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

    protected function warehouseRules(?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('warehouses', 'warehouse_code');

        if ($ignoreId) {
            $codeRule = $codeRule->ignore($ignoreId);
        }

        return [
            'warehouse_name' => ['required', 'string', 'max:255'],
            'warehouse_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/', $codeRule],
            'image' => ['nullable', 'image', 'max:4096'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in([Warehouse::STATUS_ACTIVE, Warehouse::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'warehouse_code' => strtoupper($validated['warehouse_code']),
            'contact_person' => $validated['contact_person'],
            'contact_number' => $validated['contact_number'],
            'email' => $validated['email'] ?: null,
            'country' => $validated['country'],
            'state' => $validated['state'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'postal_code' => $validated['postal_code'] ?: null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?: null,
        ];
    }
}
