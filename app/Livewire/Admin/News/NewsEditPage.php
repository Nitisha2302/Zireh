<?php

namespace App\Livewire\Admin\News;

use App\Models\News;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit News'])]
class NewsEditPage extends Component
{
    use WithFileUploads;

    public News $news;

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

    public function mount(News $news): void
    {
        $this->news = $news;
        $this->title = [
            'en' => $news->getTranslation('title', 'en'),
            'ru' => $news->getTranslation('title', 'ru'),
            'tg' => $news->getTranslation('title', 'tg'),
        ];
        $this->description = [
            'en' => $news->getTranslation('description', 'en'),
            'ru' => $news->getTranslation('description', 'ru'),
            'tg' => $news->getTranslation('description', 'tg'),
        ];
        $this->isActive = $news->is_active;
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
                $fileManager->delete($this->news->image);
                $attributes['image'] = $fileManager->store($this->image, 'news');
            }

            $this->news->update($attributes);

            flash()->success('News updated successfully.');
            $this->redirectRoute('admin.news.index');
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
        return view('livewire.admin.news.news-edit-page');
    }
}
