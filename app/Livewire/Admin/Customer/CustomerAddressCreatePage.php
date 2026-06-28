<?php

namespace App\Livewire\Admin\Customer;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\Auth\UserAddressService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Create Customer Address'])]
class CustomerAddressCreatePage extends Component
{
    public User $customer;

    public array $address = [
        'full_name' => '',
        'phone' => '',
        'alternate_phone' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'landmark' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
        'address_type' => UserAddress::TYPE_HOME,
        'latitude' => '',
        'longitude' => '',
        'is_default' => false,
    ];

    public function mount(User $customer): void
    {
        $this->customer = $customer;
        $this->address['full_name'] = $customer->name ?? '';
        $this->address['phone'] = $customer->phone ?? '';
        $this->address['is_default'] = ! $customer->addresses()->exists();
    }

    protected function rules(): array
    {
        return $this->addressRules();
    }

    public function save(UserAddressService $service): void
    {
        $service->create($this->customer, $this->validate()['address']);

        flash()->success('Address created successfully.');
        $this->redirectRoute('admin.customers.addresses.index', ['customer' => $this->customer->id]);
    }

    public function render()
    {
        return view('livewire.admin.customer.customer-address-create-page', [
            'addressTypes' => $this->addressTypes(),
        ]);
    }

    private function addressRules(): array
    {
        return [
            'address.full_name' => ['required', 'string', 'max:255'],
            'address.phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/'],
            'address.alternate_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/'],
            'address.address_line_1' => ['required', 'string', 'max:255'],
            'address.address_line_2' => ['nullable', 'string', 'max:255'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:120'],
            'address.state' => ['required', 'string', 'max:120'],
            'address.country' => ['required', 'string', 'max:120'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.address_type' => ['required', 'in:home,work,other'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address.is_default' => ['boolean'],
        ];
    }

    private function addressTypes(): array
    {
        return [
            UserAddress::TYPE_HOME => 'Home',
            UserAddress::TYPE_WORK => 'Work',
            UserAddress::TYPE_OTHER => 'Other',
        ];
    }
}
