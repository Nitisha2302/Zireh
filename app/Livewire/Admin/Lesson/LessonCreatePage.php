<?php

namespace App\Livewire\Admin\Lesson;

use App\Models\Lesson;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Create Lesson'])]
class LessonCreatePage extends Component
{
    use WithFileUploads;

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

    protected function rules(): array
    {
        return [
            'title.en' => ['required', 'string', 'max:255'],
            'title.ru' => ['required', 'string', 'max:255'],
            'title.tg' => ['required', 'string', 'max:255'],
            'description.en' => ['required', 'string'],
            'description.ru' => ['required', 'string'],
            'description.tg' => ['required', 'string'],
            'image' => ['required', 'image', 'max:4096'],
            'isActive' => ['boolean'],
        ];
    }

    public function updatedTitleEn($value): void
    {
        if (filled($value)) {
            $this->dispatch('switch-tab', tab: 'ru');
        }
    }

    public function updatedTitleRu($value): void
    {
        if (filled($value)) {
            $this->dispatch('switch-tab', tab: 'tg');
        }
    }

    public function save(FileManager $fileManager): void
    {
        $this->resetErrorBag();

        try {
            $validated = $this->validate();

            Lesson::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image' => $fileManager->store($this->image, 'lessons'),
                'is_active' => $validated['isActive'],
            ]);

            flash()->success('Lesson created successfully.');
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
        return view('livewire.admin.lesson.lesson-create-page');
    }
}
