<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\Warehouse;
use App\Services\FileManager;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit Warehouse'])]
class WarehouseEditPage extends Component
{
    use WithFileUploads;

    public Warehouse $warehouse;

    public string $warehouse_name = '';

    public string $warehouse_code = '';

    public mixed $image = null;

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

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
        $this->warehouse_name = $warehouse->warehouse_name;
        $this->warehouse_code = $warehouse->warehouse_code;
        $this->contact_person = $warehouse->contact_person;
        $this->contact_number = $warehouse->contact_number;
        $this->email = $warehouse->email ?? '';
        $this->country = $warehouse->country;
        $this->state = $warehouse->state;
        $this->city = $warehouse->city;
        $this->address = $warehouse->address;
        $this->postal_code = $warehouse->postal_code ?? '';
        $this->latitude = (string) $warehouse->latitude;
        $this->longitude = (string) $warehouse->longitude;
        $this->status = $warehouse->status;
        $this->notes = $warehouse->notes ?? '';
    }

    protected function rules(): array
    {
        return $this->warehouseRules($this->warehouse->id);
    }

    public function update(FileManager $fileManager): void
    {
        $validated = $this->validate();
        $data = $this->mapValidated($validated);

        if ($this->image) {
            $fileManager->delete($this->warehouse->image);
            $data['image'] = $fileManager->store($this->image, 'warehouses');
        }

        $this->warehouse->update($data);

        flash()->success(__('admin.warehouse_updated'));
        $this->redirectRoute('admin.warehouses.show', $this->warehouse);
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-edit-page', [
            'statuses' => Warehouse::statuses(),
        ])->title(__('admin.edit_warehouse'));
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
