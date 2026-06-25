<?php

namespace App\Livewire\Admin\PlatformSlider;

use App\Models\Platform;
use App\Models\PlatformSlider;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Edit Platform Slider'])]
class PlatformSliderEditPage extends Component
{
    use WithFileUploads;

    public PlatformSlider $platformSlider;
    public string $heading = '';
    public string $link = '';
    public mixed $image = null;
    public array $platformIds = [];

    public function mount(PlatformSlider $platformSlider): void
    {
        $this->platformSlider = $platformSlider;
        $this->heading = $platformSlider->heading;
        $this->link = $platformSlider->link ?? '';
        $this->platformIds = $platformSlider->platforms()->pluck('platforms.id')->all();
    }

    protected function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'image', 'max:4096'],
            'platformIds' => ['required', 'array', 'min:1'],
            'platformIds.*' => ['integer', 'exists:platforms,id'],
        ];
    }

    public function update(FileManager $fileManager): void
    {
        $validated = $this->validate();
        $attributes = [
            'heading' => $validated['heading'],
            'link' => $validated['link'] ?: null,
        ];

        if ($this->image) {
            $fileManager->delete($this->platformSlider->image);
            $attributes['image'] = $fileManager->store($this->image, 'platform-sliders');
        }

        $this->platformSlider->update($attributes);
        $this->platformSlider->platforms()->sync($validated['platformIds']);
        PlatformSlider::clearCatalogCache();

        flash()->success('Platform slider updated successfully.');
        $this->redirectRoute('admin.platform-sliders.index');
    }

    public function render()
    {
        return view('livewire.admin.platform-slider.platform-slider-edit-page', [
            'platforms' => Platform::query()->orderBy('name->en')->get(),
        ]);
    }
}
