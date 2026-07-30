<?php

namespace App\Livewire\Admin\Settings;

use App\Models\RapidApiLog;
use App\Services\RapidApi\RapidApiLogger;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'RapidAPI Logs'])]
class RapidApiLogListPage extends Component
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

    public function clearOldLogs(RapidApiLogger $logger): void
    {
        $deleted = $logger->purgeOlderThanDays(30);

        session()->flash('success', __('admin.rapid_api_logs_purged', ['count' => $deleted]));
    }

    public function render(RapidApiLogger $logger)
    {
        $logs = $logger->listForAdmin($this->filters(), 20);

        return view('livewire.admin.settings.rapid-api-log-list-page', [
            'logs' => $logs,
            'sources' => [
                RapidApiLog::SOURCE_API => __('admin.rapid_api_log_source_api'),
            ],
        ])->title(__('admin.rapid_api_logs'));
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
