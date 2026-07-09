<?php

namespace App\Livewire\Admin\News;

use App\Models\News;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'News'])]
class NewsListPage extends Component
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
        $news = News::findOrFail($id);
        $news->update(['is_active' => ! $news->is_active]);
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this news item?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        $news = News::findOrFail($this->deleteId);
        app(FileManager::class)->delete($news->image);
        $news->delete();
        $this->deleteId = null;
        flash()->success('News deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
    }

    public function render()
    {
        $newsItems = News::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.news.news-list-page', [
            'newsItems' => $newsItems,
        ]);
    }
}
