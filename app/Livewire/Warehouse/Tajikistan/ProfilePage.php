<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Livewire\Warehouse\Concerns\ManagesWarehouseProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Profile'])]
class ProfilePage extends Component
{
    use ManagesWarehouseProfile;

    public function render()
    {
        return $this->renderProfile(__('admin.tajikistan_warehouse_panel'));
    }
}
