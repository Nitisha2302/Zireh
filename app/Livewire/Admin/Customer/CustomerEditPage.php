<?php

namespace App\Livewire\Admin\Customer;

use App\Models\User;
use App\Services\CustomerManagementService;
use App\Services\FileManager;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit Customer'])]
class CustomerEditPage extends Component
{
    use WithFileUploads;

    public User $customer;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $status = User::STATUS_ACTIVE;

    public string $preferred_language = 'en';

    public string $device_token = '';

    public string $password = '';

    public string $password_confirmation = '';

    public mixed $profile_photo = null;

    public function mount(User $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name ?? '';
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->status = $customer->status ?? User::STATUS_ACTIVE;
        $this->preferred_language = $customer->preferred_language ?? 'en';
        $this->device_token = $customer->device_token ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')->ignore($this->customer->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->customer->id)],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_BLOCKED])],
            'preferred_language' => ['nullable', 'in:en,ru,tg'],
            'device_token' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function update(CustomerManagementService $service, FileManager $fileManager): void
    {
        $validated = $this->validate();

        if ($this->profile_photo) {
            $fileManager->delete($this->customer->profile_photo);
            $validated['profile_photo'] = $fileManager->store($this->profile_photo, 'customers/profile-photos');
        } else {
            unset($validated['profile_photo']);
        }

        if (($validated['password'] ?? '') === '') {
            unset($validated['password']);
        }

        $service->update($this->customer, $validated);

        flash()->success('Customer updated successfully.');
        $this->redirectRoute('admin.customers.index');
    }

    public function render()
    {
        return view('livewire.admin.customer.customer-edit-page', [
            'statuses' => [
                User::STATUS_ACTIVE => 'Active',
                User::STATUS_INACTIVE => 'Inactive',
                User::STATUS_BLOCKED => 'Blocked',
            ],
            'languages' => [
                'en' => 'English',
                'ru' => 'Russian',
                'tg' => 'Tajik',
            ],
        ]);
    }
}
