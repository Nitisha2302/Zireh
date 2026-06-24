<?php

namespace App\Livewire\Admin\PlatformSlider;

use App\Models\PlatformSlider;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Platform Sliders'])]
class PlatformSliderListPage extends Component
{
    use WithPagination;

    public ?int $deleteId = null;

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info('Are you sure you want to delete this platform slider?');
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        $slider = PlatformSlider::findOrFail($this->deleteId);
        app(FileManager::class)->delete($slider->image);
        $slider->delete();
        $this->deleteId = null;
        flash()->success('Platform slider deleted successfully.');
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.platform-slider.platform-slider-list-page', [
            'sliders' => PlatformSlider::query()->with('platforms')->latest()->paginate(15),
        ]);
    }
}
