<?php

namespace App\Livewire\Admin\Lesson;

use App\Models\Lesson;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit Lesson'])]
class LessonEditPage extends Component
{
    use WithFileUploads;

    public Lesson $lesson;

    public array $title = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public array $description = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public mixed $image = null;

    public bool $isActive = true;

    public function mount(Lesson $lesson): void
    {
        $this->lesson = $lesson;
        $this->title = [
            'en' => $lesson->getTranslation('title', 'en'),
            'ru' => $lesson->getTranslation('title', 'ru'),
            'tg' => $lesson->getTranslation('title', 'tg'),
        ];
        $this->description = [
            'en' => $lesson->getTranslation('description', 'en'),
            'ru' => $lesson->getTranslation('description', 'ru'),
            'tg' => $lesson->getTranslation('description', 'tg'),
        ];
        $this->isActive = $lesson->is_active;
    }

    protected function rules(): array
    {
        return [
            'title.en' => ['required', 'string', 'max:255'],
            'title.ru' => ['required', 'string', 'max:255'],
            'title.tg' => ['required', 'string', 'max:255'],
            'description.en' => ['required', 'string'],
            'description.ru' => ['required', 'string'],
            'description.tg' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'isActive' => ['boolean'],
        ];
    }

    public function update(FileManager $fileManager): void
    {
        $this->resetErrorBag();

        try {
            $validated = $this->validate();
            $attributes = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'is_active' => $validated['isActive'],
            ];

            if ($this->image) {
                $fileManager->delete($this->lesson->image);
                $attributes['image'] = $fileManager->store($this->image, 'lessons');
            }

            $this->lesson->update($attributes);

            flash()->success('Lesson updated successfully.');
            $this->redirectRoute('admin.lessons.index');
        } catch (ValidationException $e) {
            $this->switchTabForValidationErrors($e);

            throw $e;
        }
    }

    private function switchTabForValidationErrors(ValidationException $e): void
    {
        $errors = $e->validator->errors();

        foreach (['en', 'ru', 'tg'] as $locale) {
            if ($errors->has("title.{$locale}") || $errors->has("description.{$locale}")) {
                $this->dispatch('switch-tab', tab: $locale);

                return;
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.lesson.lesson-edit-page');
    }
}
