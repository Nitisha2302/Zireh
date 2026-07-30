<?php

namespace App\Livewire\Admin\Settings;

use App\Models\RapidApiLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'RapidAPI Log Detail'])]
class RapidApiLogDetailPage extends Component
{
    public RapidApiLog $log;

    public function mount(RapidApiLog $log): void
    {
        $this->log = $log;
    }

    public function render()
    {
        return view('livewire.admin.settings.rapid-api-log-detail-page')
            ->title(__('admin.rapid_api_log_detail').' #'.$this->log->id);
    }
}
