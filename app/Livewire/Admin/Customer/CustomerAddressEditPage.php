<?php

namespace App\Livewire\Admin\Customer;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\Auth\UserAddressService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Edit Customer Address'])]
class CustomerAddressEditPage extends Component
{
    public User $customer;

    public UserAddress $userAddress;

    public array $address = [];

    public function mount(User $customer, UserAddress $userAddress): void
    {
        abort_unless($userAddress->user_id === $customer->id, 404);

        $this->customer = $customer;
        $this->userAddress = $userAddress;
        $this->address = $userAddress->only([
            'full_name',
            'phone',
            'alternate_phone',
            'address_line_1',
            'address_line_2',
            'landmark',
            'city',
            'state',
            'country',
            'postal_code',
            'address_type',
            'latitude',
            'longitude',
            'is_default',
        ]);
    }

    protected function rules(): array
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

    public function update(UserAddressService $service): void
    {
        $service->update($this->customer, $this->userAddress, $this->validate()['address']);

        flash()->success('Address updated successfully.');
        $this->redirectRoute('admin.customers.addresses.index', ['customer' => $this->customer->id]);
    }

    public function render()
    {
        return view('livewire.admin.customer.customer-address-edit-page', [
            'addressTypes' => [
                UserAddress::TYPE_HOME => 'Home',
                UserAddress::TYPE_WORK => 'Work',
                UserAddress::TYPE_OTHER => 'Other',
            ],
        ]);
    }
}
