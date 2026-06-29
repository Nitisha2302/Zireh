<?php

namespace App\Livewire\Admin\PlatformCommissionSlab;

use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Commission Slabs'])]
class PlatformCommissionSlabListPage extends Component
{
    public Platform $platform;

    public ?int $deleteId = null;

    public function mount(Platform $platform): void
    {
        $this->platform = $platform;
    }

    public function toggleStatus(int $id): void
    {
        $slab = $this->platform->commissionSlabs()->findOrFail($id);
        $slab->update(['is_active' => ! $slab->is_active]);
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this commission slab?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        $this->platform->commissionSlabs()->findOrFail($this->deleteId)->delete();
        $this->deleteId = null;
        flash()->success('Commission slab deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.platform-commission-slab.platform-commission-slab-list-page', [
            'slabs' => $this->platform->commissionSlabs()->orderBy('min_amount')->get(),
        ]);
    }
}
