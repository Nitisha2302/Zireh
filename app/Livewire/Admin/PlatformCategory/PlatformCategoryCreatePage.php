<?php

namespace App\Livewire\Admin\PlatformCategory;

use App\Models\Platform;
use App\Models\PlatformCategory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Create Platform Category'])]
class PlatformCategoryCreatePage extends Component
{
    public ?int $platformId = null;

    public array $name = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public string $keyword = '';

    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'platformId' => ['required', 'integer', 'exists:platforms,id'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.tg' => ['required', 'string', 'max:255'],
            'keyword' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('platform_categories', 'keyword')->where('platform_id', $this->platformId),
            ],
            'isActive' => ['required', 'boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'platformId' => 'platform',
            'isActive' => 'status',
        ];
    }

    public function updatedNameEn($value): void
    {
        if (filled($value)) {
            $this->dispatch('switch-tab', tab: 'ru');
        }
    }

    public function updatedNameRu($value): void
    {
        if (filled($value)) {
            $this->dispatch('switch-tab', tab: 'tg');
        }
    }

    public function save(): void
    {
        $this->resetErrorBag();

        try {
            $validated = $this->validate();

            PlatformCategory::create([
                'platform_id' => $validated['platformId'],
                'name' => $validated['name'],
                'keyword' => $validated['keyword'],
                'is_active' => $validated['isActive'],
            ]);

            flash()->success('Platform category created successfully.');
            $this->redirectRoute('admin.platform-categories.index');
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
        return view('livewire.admin.platform-category.platform-category-create-page', [
            'platforms' => Platform::query()->orderBy('name->en')->get(),
        ]);
    }
}
