<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h4 class="mb-1">{{ __('admin.alif_api_log_detail') }} #{{ $log->id }}</h4>
      <p class="mb-0 text-body-secondary">{{ $log->created_at?->format('d M Y H:i:s') }}</p>
    </div>
    <a href="{{ route('admin.settings.alif-api-logs.index') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-arrow-left me-1"></i>{{ __('admin.back') }}
    </a>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">{{ __('admin.summary') }}</h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-5">{{ __('admin.alif_api_log_action') }}</dt>
            <dd class="col-sm-7"><span class="badge bg-label-info">{{ $log->action ?: '—' }}</span></dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_payment_id') }}</dt>
            <dd class="col-sm-7 font-monospace small">{{ $log->payment_id ?: '—' }}</dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_account') }}</dt>
            <dd class="col-sm-7 font-monospace small">{{ $log->account ?: '—' }}</dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_amount') }}</dt>
            <dd class="col-sm-7">{{ $log->amount !== null ? number_format((float) $log->amount, 2) : '—' }}</dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_code') }}</dt>
            <dd class="col-sm-7">
              <span class="badge bg-label-{{ $log->codeBadgeClass() }}">
                {{ $log->response_code ?? '—' }} — {{ $log->codeLabel() ?? ($log->is_successful ? __('admin.success') : __('admin.failed')) }}
              </span>
            </dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_authorized') }}</dt>
            <dd class="col-sm-7">
              <span class="badge bg-label-{{ $log->authorized ? 'success' : 'danger' }}">
                {{ $log->authorized ? __('admin.yes') : __('admin.no') }}
              </span>
            </dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_duration') }}</dt>
            <dd class="col-sm-7">{{ $log->formattedDuration() ?? '—' }}</dd>

            <dt class="col-sm-5">{{ __('admin.alif_api_log_ip') }}</dt>
            <dd class="col-sm-7 font-monospace small">{{ $log->ip_address ?: '—' }}</dd>

            @if ($log->payment)
              <dt class="col-sm-5">{{ __('admin.alif_api_log_payment') }}</dt>
              <dd class="col-sm-7">
                <span class="badge bg-label-{{ $log->payment->statusBadgeClass() }}">{{ $log->payment->status }}</span>
                @if ($log->payment->user_id)
                  <a href="{{ route('admin.customers.wallet', $log->payment->user_id) }}" class="d-block small mt-1">
                    {{ __('admin.alif_api_log_view_payment') }}
                  </a>
                @endif
              </dd>
            @endif

            @if ($log->error_message)
              <dt class="col-sm-5">{{ __('admin.error') }}</dt>
              <dd class="col-sm-7 text-danger">{{ $log->error_message }}</dd>
            @endif

            @if ($log->response_truncated)
              <dt class="col-sm-5">{{ __('admin.note') }}</dt>
              <dd class="col-sm-7 text-warning">{{ __('admin.alif_api_log_response_truncated') }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('admin.alif_api_log_request') }}</h5>
        </div>
        <div class="card-body">
          @if ($log->request_payload)
            <pre class="bg-lighter rounded p-3 mb-0 small overflow-auto" style="max-height: 420px;">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
          @else
            <p class="mb-0 text-body-secondary">{{ __('admin.alif_api_log_no_request_body') }}</p>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">{{ __('admin.alif_api_log_response') }}</h5>
        </div>
        <div class="card-body">
          @if ($log->response_body)
            <pre class="bg-lighter rounded p-3 mb-0 small overflow-auto" style="max-height: 520px;">{{ json_encode($log->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
          @else
            <p class="mb-0 text-body-secondary">{{ __('admin.alif_api_log_no_response_body') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
