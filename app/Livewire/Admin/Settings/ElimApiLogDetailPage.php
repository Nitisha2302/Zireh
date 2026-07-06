<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ElimApiLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Elim API Log Detail'])]
class ElimApiLogDetailPage extends Component
{
    public ElimApiLog $log;

    public function mount(ElimApiLog $log): void
    {
        $this->log = $log;
    }

    public function render()
    {
        return view('livewire.admin.settings.elim-api-log-detail-page')
            ->title(__('admin.elim_api_log_detail').' #'.$this->log->id);
    }
}
