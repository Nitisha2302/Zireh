<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="icon-base ti tabler-alert-triangle me-1"></i>
                    {{ __('admin.report_exception') }}
                </h5>
            </div>
            <div class="card-body">
                <form wire:submit="createException">
                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.exception_type') }}</label>
                        <select class="form-select @error('newType') is-invalid @enderror" wire:model="newType">
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ __('admin.exception_type_'.$type) }}</option>
                            @endforeach
                        </select>
                        @error('newType') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.order_identifier_optional') }}</label>
                        <input
                            type="text"
                            class="form-control @error('newOrderIdentifier') is-invalid @enderror"
                            wire:model="newOrderIdentifier"
                            placeholder="PCL-... / ORD... / #ID"
                        >
                        @error('newOrderIdentifier') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.scanned_code_optional') }}</label>
                        <input
                            type="text"
                            class="form-control @error('newScannedCode') is-invalid @enderror"
                            wire:model="newScannedCode"
                            placeholder="{{ __('admin.scan_barcode_placeholder') }}"
                        >
                        @error('newScannedCode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.exception_summary') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('newSummary') is-invalid @enderror"
                            wire:model="newSummary"
                            placeholder="{{ __('admin.exception_summary_placeholder') }}"
                        >
                        @error('newSummary') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.details') }}</label>
                        <textarea class="form-control" rows="2" wire:model="newDetails"></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100" wire:loading.attr="disabled" wire:target="createException">
                        {{ __('admin.report_exception') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input
                            type="text"
                            class="form-control"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('admin.search_exceptions_placeholder') }}"
                        >
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" wire:model.live="typeFilter">
                            <option value="">{{ __('admin.all_exception_types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ __('admin.exception_type_'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">{{ __('admin.all_statuses') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ __('admin.exception_status_'.$status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('admin.exception_type') }}</th>
                            <th>{{ __('admin.orders') }}</th>
                            <th>{{ __('admin.exception_summary') }}</th>
                            <th>{{ __('admin.status') }}</th>
                            <th>{{ __('admin.created_date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exceptions as $exception)
                            <tr>
                                <td class="fw-semibold">#{{ $exception->id }}</td>
                                <td>
                                    <span class="badge {{ $exception->typeBadgeClass() }}">{{ $exception->typeLabel() }}</span>
                                    @if ($exception->scanned_code)
                                        <div class="small mt-1"><code>{{ $exception->scanned_code }}</code></div>
                                    @endif
                                </td>
                                <td>
                                    @if ($exception->order)
                                        <a href="{{ route($orderShowRoute, $exception->order) }}" class="fw-semibold">
                                            #{{ $exception->order->id }} · {{ $exception->order->parcel_code }}
                                        </a>
                                        <div class="small text-body-secondary">{{ $exception->order->elim_order_id ?? '—' }}</div>
                                    @else
                                        <span class="badge bg-label-secondary">{{ __('admin.unlinked_order') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $exception->summary }}</div>
                                    @if ($exception->details)
                                        <small class="text-body-secondary d-block">{{ $exception->details }}</small>
                                    @endif
                                    @if ($exception->resolution_notes)
                                        <small class="text-success d-block mt-1">
                                            <strong>{{ __('admin.resolution_notes') }}:</strong> {{ $exception->resolution_notes }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $exception->statusBadgeClass() }}">{{ $exception->statusLabel() }}</span>
                                </td>
                                <td class="small text-body-secondary">
                                    {{ $exception->created_at?->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-label-primary"
                                        wire:click="startReview({{ $exception->id }})"
                                    >
                                        {{ __('admin.review_exception') }}
                                    </button>
                                </td>
                            </tr>
                            @if ($editingExceptionId === $exception->id)
                                <tr class="bg-light">
                                    <td colspan="7" class="p-3">
                                        <form wire:submit="saveReview" class="row g-3 align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label small">{{ __('admin.status') }}</label>
                                                <select class="form-select form-select-sm" wire:model="editStatus">
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}">{{ __('admin.exception_status_'.$status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">{{ __('admin.link_to_order') }}</label>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-sm @error('linkOrderIdentifier') is-invalid @enderror"
                                                    wire:model="editLinkOrderIdentifier"
                                                    placeholder="PCL-... / ORD... / #ID"
                                                >
                                                @error('linkOrderIdentifier') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">{{ __('admin.resolution_notes') }}</label>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    wire:model="editResolutionNotes"
                                                    placeholder="{{ __('admin.resolution_notes') }}"
                                                >
                                            </div>
                                            <div class="col-md-2 d-flex gap-1">
                                                <button type="submit" class="btn btn-sm btn-success w-100">{{ __('admin.save') }}</button>
                                                <button type="button" class="btn btn-sm btn-label-secondary" wire:click="cancelReview">×</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-body-secondary">
                                    {{ __('admin.no_exceptions_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($exceptions->hasPages())
                <div class="card-footer">
                    {{ $exceptions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
