<div class="card {{ $class ?? 'mb-4' }}">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-clock"></i>
            {{ __('admin.working_hours') }}
        </h5>
        @if ($warehouse->isOpenNow())
            <span class="badge bg-label-success">{{ __('admin.open_status') }}</span>
        @else
            <span class="badge bg-label-danger">{{ __('admin.closed') }}</span>
        @endif
    </div>
    <div class="card-body">
        @if ($warehouse->contact_number)
            <p class="mb-4 d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-phone text-primary"></i>
                <span class="fw-medium">{{ __('admin.phone') }}: {{ $warehouse->contact_number }}</span>
            </p>
        @endif
        <div class="d-flex flex-column gap-2">
            @foreach ($warehouse->workingHoursPayload() as $hours)
                <div class="d-flex align-items-start gap-3 border rounded px-3 py-2">
                    <span class="border-start border-3 border-primary rounded-1 align-self-stretch"></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            @if ($hours['is_closed'])
                                {{ $hours['day_name'] }}: {{ __('admin.closed') }}
                            @else
                                {{ $hours['day_name'] }}: {{ $hours['opens_at'] }}–{{ $hours['closes_at'] }}
                            @endif
                        </div>
                        @if (! $hours['is_closed'] && $hours['break_starts_at'] && $hours['break_ends_at'])
                            <small class="text-body-secondary">{{ __('admin.break') }} {{ $hours['break_starts_at'] }}–{{ $hours['break_ends_at'] }}</small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
