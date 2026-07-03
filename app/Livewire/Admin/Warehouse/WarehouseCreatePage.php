<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\Warehouse;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Warehouse'])]
class WarehouseCreatePage extends Component
{
    public string $warehouse_name = '';

    public string $warehouse_code = '';

    public string $contact_person = '';

    public string $contact_number = '';

    public string $email = '';

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
        return $this->warehouseRules();
    }

    public function save(): void
    {
        $validated = $this->validate();

        Warehouse::create($this->mapValidated($validated));

        flash()->success(__('admin.warehouse_created'));
        $this->redirectRoute('admin.warehouses.index');
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-create-page', [
            'statuses' => Warehouse::statuses(),
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
