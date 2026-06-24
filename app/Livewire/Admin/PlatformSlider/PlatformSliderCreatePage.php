<?php

namespace App\Livewire\Admin\PlatformSlider;

use App\Models\Platform;
use App\Models\PlatformSlider;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin', ['title' => 'Create Platform Slider'])]
class PlatformSliderCreatePage extends Component
{
    use WithFileUploads;

    public string $heading = '';
    public string $link = '';
    public mixed $image = null;
    public array $platformIds = [];

    protected function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:2048'],
            'image' => ['required', 'image', 'max:4096'],
            'platformIds' => ['required', 'array', 'min:1'],
            'platformIds.*' => ['integer', 'exists:platforms,id'],
        ];
    }

    public function save(FileManager $fileManager): void
    {
        $validated = $this->validate();

        $slider = PlatformSlider::create([
            'heading' => $validated['heading'],
            'link' => $validated['link'] ?: null,
            'image' => $fileManager->store($this->image, 'platform-sliders'),
        ]);

        $slider->platforms()->sync($validated['platformIds']);

        flash()->success('Platform slider created successfully.');
        $this->redirectRoute('admin.platform-sliders.index');
    }

    public function render()
    {
        return view('livewire.admin.platform-slider.platform-slider-create-page', [
            'platforms' => Platform::query()->where('is_available', true)->orderBy('name->en')->get(),
        ]);
    }
}
