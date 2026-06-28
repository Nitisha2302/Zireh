<?php

namespace App\Livewire\Admin\Customer;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\Auth\UserAddressService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Customer Addresses'])]
class CustomerAddressListPage extends Component
{
    use WithPagination;

    public User $customer;

    public string $search = '';

    public ?int $deleteId = null;

    public function mount(User $customer): void
    {
        $this->customer = $customer;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setDefault(int $id): void
    {
        app(UserAddressService::class)->setDefault($this->customer, UserAddress::findOrFail($id));
        flash()->success('Default address updated successfully.');
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this address?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        app(UserAddressService::class)->delete($this->customer, UserAddress::findOrFail($this->deleteId));

        $this->deleteId = null;
        flash()->success('Address deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info('Deletion cancelled.');
    }

    public function render()
    {
        $addresses = $this->customer
            ->addresses()
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('city', 'like', "%{$this->search}%")
                        ->orWhere('state', 'like', "%{$this->search}%")
                        ->orWhere('country', 'like', "%{$this->search}%")
                        ->orWhere('postal_code', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('is_default')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.customer.customer-address-list-page', [
            'addresses' => $addresses,
        ]);
    }
}
