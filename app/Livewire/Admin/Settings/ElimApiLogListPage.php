<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ElimApiLog;
use App\Services\Elim\ElimApiLogger;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Elim API Logs'])]
class ElimApiLogListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $methodFilter = '';

    public string $sourceFilter = '';

    public string $successFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSuccessFilter(): void
    {
        $this->resetPage();
    }

    public function clearOldLogs(ElimApiLogger $logger): void
    {
        $deleted = $logger->purgeOlderThanDays(30);

        session()->flash('success', __('admin.elim_api_logs_purged', ['count' => $deleted]));
    }

    public function render(ElimApiLogger $logger)
    {
        $logs = $logger->listForAdmin($this->filters(), 20);

        return view('livewire.admin.settings.elim-api-log-list-page', [
            'logs' => $logs,
            'sources' => [
                ElimApiLog::SOURCE_API => __('admin.elim_api_log_source_api'),
                ElimApiLog::SOURCE_AUTH => __('admin.elim_api_log_source_auth'),
                ElimApiLog::SOURCE_AUTH_TEST => __('admin.elim_api_log_source_auth_test'),
                ElimApiLog::SOURCE_UPLOAD => __('admin.elim_api_log_source_upload'),
            ],
        ])->title(__('admin.elim_api_logs'));
    }

    protected function filters(): array
    {
        return array_filter([
            'search' => $this->search,
            'method' => $this->methodFilter,
            'source' => $this->sourceFilter,
            'is_successful' => $this->successFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
