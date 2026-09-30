<?php

namespace App\Livewire\Admin\Settings;

use App\Services\Alif\AlifApiLogger;
use App\Services\Alif\AlifPaymentService;
use App\Support\Alif\AlifResponseCode;
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
                AlifPaymentService::ACTION_CHECK => __('admin.alif_action_check'),
                AlifPaymentService::ACTION_PAY => __('admin.alif_action_pay'),
                AlifPaymentService::ACTION_STATUS => __('admin.alif_action_status'),
            ],
            'codes' => $this->codeOptions(),
            'retentionDays' => $this->retentionDays(),
        ])->title(__('admin.alif_api_logs'));
    }

    protected function codeOptions(): array
    {
        $options = [];

        foreach (AlifResponseCode::cases() as $case) {
            $options[$case->value] = $case->value.' — '.$case->label();
        }

        ksort($options);

        return $options;
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
