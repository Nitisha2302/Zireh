<?php

namespace App\Livewire\Admin\Platform;

use Livewire\Component;
use App\Models\Platform;
use Livewire\Attributes\Layout;
use App\Services\FileManager;
use Illuminate\Validation\ValidationException;
#[Layout('layouts::admin', ['title' => 'Plateform Create'])]
class PlatformCreatePage extends Component
{
    public array $name = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public array $images = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public bool $is_available = true;

    protected function rules(): array
    {
        return [
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.tg' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
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

            Platform::create([
                'name' => $validated['name'],
                'image' => $fileManager->store($this->image, 'cuisines'),
                'is_available' => $validated['status'],
            ]);

            flash()->success(
                'Platform created successfully.'
            );

            $this->redirectRoute('admin.platform.index');
        } catch (ValidationException $e) {

            $errors = $e->validator->errors();

            if ($errors->has('name.en')) {
                $this->dispatch('switch-tab', tab: 'en');
            } elseif ($errors->has('name.ru')) {
                $this->dispatch('switch-tab', tab: 'ru');
            } elseif ($errors->has('name.tg')) {
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
