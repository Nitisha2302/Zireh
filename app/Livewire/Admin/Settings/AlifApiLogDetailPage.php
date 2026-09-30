<?php

namespace App\Livewire\Admin\Settings;

use App\Models\AlifApiLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Alif Log Detail'])]
class AlifApiLogDetailPage extends Component
{
    public AlifApiLog $log;

    public function mount(AlifApiLog $log): void
    {
        $this->log = $log->load('payment');
    }

    public function render()
    {
        return view('livewire.admin.settings.alif-api-log-detail-page')
            ->title(__('admin.alif_api_log_detail').' #'.$this->log->id);
    }
}
