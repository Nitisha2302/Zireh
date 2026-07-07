<?php

namespace App\Livewire\Warehouse\China;

use App\Livewire\Warehouse\Concerns\ManagesWarehouseProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'Profile'])]
class ProfilePage extends Component
{
    use ManagesWarehouseProfile;

    public function render()
    {
        return $this->renderProfile(__('admin.china_warehouse_panel'));
    }
}
