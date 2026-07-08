<?php

namespace App\Livewire\Admin\Customer;

use App\Models\User;
use App\Services\CustomerManagementService;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Customers'])]
class CustomerListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $customer = User::findOrFail($id);
        app(CustomerManagementService::class)->toggleStatus($customer);

        flash()->success('Customer status updated successfully.');
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this customer?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        $customer = User::findOrFail($this->deleteId);
        app(CustomerManagementService::class)->delete($customer, app(FileManager::class));

        $this->deleteId = null;
        flash()->success('Customer deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info('Deletion cancelled.');
    }

    public function render()
    {
        $customers = User::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.customer.customer-list-page', [
            'customers' => $customers,
            'statuses' => $this->statuses(),
        ]);
    }

    private function statuses(): array
    {
        return [
            User::STATUS_ACTIVE => 'Active',
            User::STATUS_INACTIVE => 'Inactive',
            User::STATUS_BLOCKED => 'Blocked',
        ];
    }
}
