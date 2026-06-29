<?php

namespace App\Livewire\Admin\Platform;

use Livewire\Component;
use App\Models\Platform;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;

#[Layout('layouts::admin', ['title' => 'Plateform Create'])]
class PlatformCreatePage extends Component
{
    use WithFileUploads;

    public array $name = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public array $logos = [
        'en' => null,
        'ru' => null,
        'tg' => null,
    ];

    public bool $is_available = true;

    public string $code = '';

    protected function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9]+$/', 'unique:platforms,code'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.tg' => ['required', 'string', 'max:255'],
            'logos.en' => ['nullable', 'image', 'max:2048'],
            'logos.ru' => ['nullable', 'image', 'max:2048'],
            'logos.tg' => ['nullable', 'image', 'max:2048'],
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function updatedNameEn($value)
    {
        if (filled($value)) {
            $this->dispatch('switch-tab', tab: 'ru');
        }
    }

    public function updatedNameRu($value)
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

            $logos = [];
            foreach (array_keys($this->logos) as $locale) {
                if ($this->logos[$locale]) {
                    $logos[$locale] = $fileManager->store($this->logos[$locale], 'platforms/logos');
                }
            }

            Platform::create([
                'code' => filled($validated['code']) ? $validated['code'] : null,
                'name' => $validated['name'],
                'logo' => $logos,
                'is_available' => $validated['is_available'],
            ]);

            flash()->success(
                'Platform created successfully.'
            );

            $this->redirectRoute('admin.platforms.index');
        } catch (ValidationException $e) {

            $errors = $e->validator->errors();

            if ($errors->has('name.en') || $errors->has('logos.en')) {
                $this->dispatch('switch-tab', tab: 'en');
            } elseif ($errors->has('name.ru') || $errors->has('logos.ru')) {
                $this->dispatch('switch-tab', tab: 'ru');
            } elseif ($errors->has('name.tg') || $errors->has('logos.tg')) {
                $this->dispatch('switch-tab', tab: 'tg');
            }

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.admin.platform.platform-create-page');
    }
}
