<?php

namespace App\Livewire\Admin\Plateform;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts::admin', ['title' => 'Plateform List'])]
class PlateformListPage extends Component
{
    public function render()
    {
        return view('livewire.admin.plateform.plateform-list-page')->title(__('admin.plateforms'));;
    }
}
