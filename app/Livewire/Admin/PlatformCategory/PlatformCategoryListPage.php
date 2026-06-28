<?php

namespace App\Livewire\Admin\PlatformCategory;

use App\Models\Platform;
use App\Models\PlatformCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Platform Categories'])]
class PlatformCategoryListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $platformFilter = '';

    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPlatformFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $category = PlatformCategory::findOrFail($id);

        $category->update([
            'is_active' => ! $category->is_active,
        ]);
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this platform category?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        PlatformCategory::findOrFail($this->deleteId)->delete();
        $this->deleteId = null;
        flash()->success('Platform category deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
    }

    public function render()
    {
        $categories = PlatformCategory::query()
            ->with('platform')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name->en', 'like', "%{$this->search}%")
                        ->orWhere('name->ru', 'like', "%{$this->search}%")
                        ->orWhere('name->tg', 'like', "%{$this->search}%")
                        ->orWhere('keyword', 'like', "%{$this->search}%");
                });
            })
            ->when($this->platformFilter, fn ($query) => $query->where('platform_id', $this->platformFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.platform-category.platform-category-list-page', [
            'categories' => $categories,
            'platforms' => Platform::query()->orderBy('name->en')->get(),
        ]);
    }
}
