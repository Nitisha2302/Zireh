<div class="container-xxl flex-grow-1 container-p-y">
  @if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h4 class="mb-1">{{ __('admin.alif_api_logs') }}</h4>
          <p class="mb-0 text-body-secondary">{{ __('admin.alif_api_logs_description') }}</p>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('admin.settings.alif') }}" class="btn btn-label-primary">
            <i class="icon-base ti tabler-settings me-1"></i>{{ __('admin.alif_api_settings') }}
          </a>
          <button type="button" class="btn btn-label-danger" wire:click="clearOldLogs"
            wire:confirm="{{ __('admin.alif_api_logs_purge_confirm', ['days' => $retentionDays]) }}">
            <i class="icon-base ti tabler-trash me-1"></i>{{ __('admin.alif_api_logs_purge', ['days' => $retentionDays]) }}
          </button>
        </div>
      </div>
    </div>

    <div class="card-body border-top border-bottom">
      <div class="row g-3">
        <div class="col-md-4">
          <input type="text" class="form-control" placeholder="{{ __('admin.alif_api_logs_search_placeholder') }}" wire:model.live.debounce.500ms="search">
        </div>
        <div class="col-md-2">
          <select class="form-select" wire:model.live="actionFilter">
            <option value="">{{ __('admin.alif_api_logs_all_actions') }}</option>
            @foreach ($actions as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" wire:model.live="codeFilter">
            <option value="">{{ __('admin.alif_api_logs_all_codes') }}</option>
            @foreach ($codes as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" wire:model.live="successFilter">
            <option value="">{{ __('admin.alif_api_logs_all_statuses') }}</option>
            <option value="1">{{ __('admin.success') }}</option>
            <option value="0">{{ __('admin.failed') }}</option>
          </select>
        </div>
        <div class="col-md-2 text-md-end">
          <span class="badge bg-label-primary fs-6">{{ __('admin.total') }}: {{ $logs->total() }}</span>
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control" wire:model.live="dateFrom">
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control" wire:model.live="dateTo">
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>{{ __('admin.date') }}</th>
            <th>{{ __('admin.alif_api_log_action') }}</th>
            <th>{{ __('admin.alif_api_log_payment_id') }}</th>
            <th>{{ __('admin.alif_api_log_account') }}</th>
            <th>{{ __('admin.alif_api_log_amount') }}</th>
            <th>{{ __('admin.alif_api_log_code') }}</th>
            <th>{{ __('admin.alif_api_log_duration') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td>#{{ $log->id }}</td>
              <td>{{ $log->created_at?->format('d M Y H:i:s') }}</td>
              <td>
                <span class="badge bg-label-info">{{ $actions[$log->action] ?? ($log->action ?: '—') }}</span>
              </td>
              <td class="font-monospace small">{{ $log->payment_id ?: '—' }}</td>
              <td class="font-monospace small">{{ $log->account ?: '—' }}</td>
              <td>{{ $log->amount !== null ? number_format((float) $log->amount, 2) : '—' }}</td>
              <td>
                <span class="badge bg-label-{{ $log->codeBadgeClass() }}">
                  {{ $log->response_code ?? '—' }} {{ $log->is_successful ? 'OK' : 'FAIL' }}
                </span>
              </td>
              <td>{{ $log->formattedDuration() ?? '—' }}</td>
              <td class="text-end">
                <a href="{{ route('admin.settings.alif-api-logs.show', $log) }}" class="btn btn-sm btn-label-primary">
                  {{ __('admin.view') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-body-secondary">{{ __('admin.alif_api_logs_empty') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($logs->hasPages())
      <div class="card-footer">{{ $logs->links('livewire::bootstrap') }}</div>
    @endif
  </div>
</div>
