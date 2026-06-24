<?php

namespace App\Livewire\Admin\Platform;

use Livewire\Component;
use Livewire\Attributes\Layout;
#[Layout('layouts::admin', ['title' => 'Platform Edit'])]
class PlatformEditPage extends Component
{
    public function render()
    {
        return view('livewire.admin.platform.platform-edit-page');
    }
}
