<?php

namespace App\Livewire\Admin\Platform;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Platform;

#[Layout('layouts::admin', ['title' => 'Plateform List'])]
class PlatformListPage extends Component
{
    use WithPagination;
    public string $search = '';
    public ?int $deleteId = null;


    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $platform = Platform::findOrFail($id);

        $platform->update([
            'is_available' => ! $platform->status,
        ]);
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()
            ->showDenyButton()
            ->info('Are you sure you want to delete the Platform?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(array $payload): void
    {
        $platform = Platform::findOrFail($this->deleteId);
        $platform->delete();
        $this->deleteId = null;
        flash()->info('Platform successfully deleted.');
    }

    #[On('sweetalert:denied')]
    public function onDeny(array $payload): void
    {
        $this->deleteId = null;
        flash()->info('Deletion cancelled.');
    }
    public function render()
    {
        $platforms = Platform::query()
            ->when($this->search, function ($query) {
                $query->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->ru', 'like', "%{$this->search}%")
                    ->orWhere('name->tg', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(15);
        return view('livewire.admin.platform.platform-list-page', compact('platforms'))->title(__('admin.platforms'));;
    }
}
