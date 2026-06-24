<?php

namespace App\Livewire\Admin\Platform;

use App\Models\Platform;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit Platform'])]
class PlatformEditPage extends Component
{
    use WithFileUploads;

    public Platform $platform;

    public array $name = ['en' => '', 'ru' => '', 'tg' => ''];
    public array $logos = ['en' => null, 'ru' => null, 'tg' => null];
    public bool $is_available = true;

    public function mount(Platform $plateform): void
    {
        $this->platform = $plateform;
        $this->name = array_replace($this->name, $plateform->getTranslations('name'));
        $this->is_available = $plateform->is_available;
    }

    protected function rules(): array
    {
        return [
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.tg' => ['required', 'string', 'max:255'],
            'logos.en' => ['nullable', 'image', 'max:2048'],
            'logos.ru' => ['nullable', 'image', 'max:2048'],
            'logos.tg' => ['nullable', 'image', 'max:2048'],
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function update(FileManager $fileManager): void
    {
        $this->resetErrorBag();

        try {
            $validated = $this->validate();
            $logos = $this->platform->getTranslations('logo');

            foreach ($this->logos as $locale => $upload) {
                if (! $upload) {
                    continue;
                }

                $fileManager->delete($logos[$locale] ?? null);
                $logos[$locale] = $fileManager->store($upload, 'platforms/logos');
            }

            $this->platform->update([
                'name' => $validated['name'],
                'logo' => $logos,
                'is_available' => $validated['is_available'],
            ]);

            flash()->success('Platform updated successfully.');
            $this->redirectRoute('admin.platforms.index');
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            foreach (['en', 'ru', 'tg'] as $locale) {
                if ($errors->has("name.{$locale}") || $errors->has("logos.{$locale}")) {
                    $this->dispatch('switch-tab', tab: $locale);
                    break;
                }
            }

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.admin.platform.platform-edit-page');
    }
}
