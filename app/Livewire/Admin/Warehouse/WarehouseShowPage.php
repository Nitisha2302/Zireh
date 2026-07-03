<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Warehouse Details'])]
class WarehouseShowPage extends Component
{
    public Warehouse $warehouse;

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-show-page', [
            'statuses' => Warehouse::statuses(),
        ])->title($this->warehouse->warehouse_name);
    }
}
