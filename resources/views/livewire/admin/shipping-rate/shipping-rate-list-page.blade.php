<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.shipping_rates') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.shipping_rates_description') }}</p>
        </div>
        <a href="{{ route('admin.shipping-rates.create') }}" class="btn btn-primary">
            <i class="icon-base ti tabler-plus me-1"></i>
            {{ __('admin.add_shipping_rate') }}
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-scale"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.total') }}</p>
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-circle-check"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.active') }}</p>
                        <h4 class="mb-0">{{ $stats['active'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="icon-base ti tabler-circle-x"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.inactive') }}</p>
                        <h4 class="mb-0">{{ $stats['inactive'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label mb-1">{{ __('admin.search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.shipping_rate_search_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label mb-1">{{ __('admin.shipping_method') }}</label>
                    <select class="form-select" wire:model.live="methodFilter">
                        <option value="">{{ __('admin.all_shipping_methods') }}</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label mb-1">{{ __('admin.status') }}</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        <option value="active">{{ __('admin.active') }}</option>
                        <option value="inactive">{{ __('admin.inactive') }}</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label mb-1">{{ __('admin.shipping_weight_range') }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" placeholder="KG" wire:model.live.debounce.500ms="weightFilter" min="0">
                        <span class="input-group-text">KG</span>
                    </div>
                </div>
                <div class="col-lg-2 d-flex flex-wrap align-items-center gap-2">
                    @if ($this->hasActiveFilters())
                        <button type="button" class="btn btn-label-secondary btn-sm" wire:click="clearFilters">
                            <i class="icon-base ti tabler-filter-off"></i>
                        </button>
                    @endif
                    <span class="badge bg-label-primary">{{ $rates->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive position-relative" wire:loading.class="opacity-50">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th>{{ __('admin.shipping_method') }}</th>
                        <th>{{ __('admin.shipping_weight_range') }}</th>
                        <th>{{ __('admin.shipping_rate_per_kg') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.created_date') }}</th>
                        <th class="text-end">{{ __('admin.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rates as $rate)
                        <tr>
                            <td>#{{ $rate->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $rate->shippingMethod?->name }}</div>
                                <code class="small">{{ $rate->shippingMethod?->code }}</code>
                            </td>
                            <td>{{ $rate->weightRangeLabel() }}</td>
                            <td class="fw-semibold">{{ number_format((float) $rate->rate_per_kg, 2) }} TJS/KG</td>
                            <td>
                                <span class="badge {{ $rate->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                    {{ $rate->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </td>
                            <td>{{ $rate->created_at?->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.shipping-rates.edit', $rate) }}">
                                            <i class="icon-base ti tabler-edit me-1"></i>{{ __('admin.edit') }}
                                        </a>
                                        <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $rate->id }})">
                                            <i class="icon-base ti tabler-toggle-left me-1"></i>
                                            {{ $rate->is_active ? __('admin.deactivate') : __('admin.activate') }}
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $rate->id }})">
                                            <i class="icon-base ti tabler-trash me-1"></i>{{ __('admin.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-body-secondary">{{ __('admin.shipping_rates_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rates->hasPages())
            <div class="card-footer">{{ $rates->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
