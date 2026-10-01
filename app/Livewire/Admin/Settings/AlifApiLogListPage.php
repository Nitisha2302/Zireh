<?php

namespace App\Livewire\Admin\Settings;

use App\Services\Alif\AlifAcquiringService;
use App\Services\Alif\AlifApiLogger;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Alif Logs'])]
class AlifApiLogListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $codeFilter = '';

    public string $successFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCodeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSuccessFilter(): void
    {
        $this->resetPage();
    }

    public function clearOldLogs(AlifApiLogger $logger): void
    {
        $days = $this->retentionDays();
        $deleted = $logger->purgeOlderThanDays($days);

        session()->flash('success', __('admin.alif_api_logs_purged', ['count' => $deleted]));
    }

    public function render(AlifApiLogger $logger)
    {
        return view('livewire.admin.settings.alif-api-log-list-page', [
            'logs' => $logger->listForAdmin($this->filters(), 20),
            'actions' => [
                AlifAcquiringService::ACTION_INIT => __('admin.alif_action_init'),
                AlifAcquiringService::ACTION_CALLBACK => __('admin.alif_action_callback'),
                AlifAcquiringService::ACTION_CHECKTXN => __('admin.alif_action_checktxn'),
            ],
            'codes' => $this->codeOptions(),
            'retentionDays' => $this->retentionDays(),
        ])->title(__('admin.alif_api_logs'));
    }

    protected function codeOptions(): array
    {
        return [
            200 => '200',
            400 => '400',
            422 => '422',
            502 => '502',
        ];
    }

    protected function retentionDays(): int
    {
        return max((int) config('alif.log_retention_days', 30), 1);
    }

    protected function filters(): array
    {
        return array_filter([
            'search' => $this->search,
            'action' => $this->actionFilter,
            'response_code' => $this->codeFilter,
            'is_successful' => $this->successFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
