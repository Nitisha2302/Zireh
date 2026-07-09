<?php

namespace App\Livewire\Admin\Lesson;

use App\Models\Lesson;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Lessons'])]
class LessonListPage extends Component
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
        $lesson = Lesson::findOrFail($id);
        $lesson->update(['is_active' => ! $lesson->is_active]);
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this lesson?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        $lesson = Lesson::findOrFail($this->deleteId);
        app(FileManager::class)->delete($lesson->image);
        $lesson->delete();
        $this->deleteId = null;
        flash()->success('Lesson deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
    }

    public function render()
    {
        $lessons = Lesson::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.lesson.lesson-list-page', [
            'lessons' => $lessons,
        ]);
    }
}
