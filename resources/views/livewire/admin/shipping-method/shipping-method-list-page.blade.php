<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.shipping_methods') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.shipping_methods_description') }}</p>
        </div>
        <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-primary">
            <i class="icon-base ti tabler-plus me-1"></i>
            {{ __('admin.add_shipping_method') }}
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-truck-delivery"></i>
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
                <div class="col-lg-5">
                    <label class="form-label mb-1">{{ __('admin.search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.shipping_method_search_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label mb-1">{{ __('admin.status') }}</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        <option value="active">{{ __('admin.active') }}</option>
                        <option value="inactive">{{ __('admin.inactive') }}</option>
                    </select>
                </div>
                <div class="col-lg-4 d-flex flex-wrap align-items-center gap-2">
                    @if ($this->hasActiveFilters())
                        <button type="button" class="btn btn-label-secondary" wire:click="clearFilters">
                            <i class="icon-base ti tabler-filter-off me-1"></i>
                            {{ __('admin.clear_filters') }}
                        </button>
                    @endif
                    <span class="badge bg-label-primary fs-6 ms-lg-auto">
                        {{ __('admin.showing_results', ['count' => $methods->total()]) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="table-responsive position-relative" wire:loading.class="opacity-50">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">ID</th>
                        <th>
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('name')">
                                {{ __('admin.shipping_method_name') }}
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('code')">
                                {{ __('admin.shipping_method_code') }}
                            </button>
                        </th>
                        <th>{{ __('admin.shipping_volumetric_divisor') }}</th>
                        <th>{{ __('admin.shipping_minimum_charge') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.created_date') }}</th>
                        <th>{{ __('admin.last_updated') }}</th>
                        <th class="text-end">{{ __('admin.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($methods as $method)
                        <tr>
                            <td>#{{ $method->id }}</td>
                            <td class="fw-semibold">{{ $method->name }}</td>
                            <td><code>{{ $method->code }}</code></td>
                            <td>{{ number_format($method->volumetric_divisor) }}</td>
                            <td>{{ number_format((float) $method->minimum_charge, 2) }} TJS</td>
                            <td>
                                <span class="badge {{ $method->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                    {{ $method->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </td>
                            <td>{{ $method->created_at?->format('M d, Y H:i') }}</td>
                            <td>{{ $method->updated_at?->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.shipping-methods.edit', $method) }}">
                                            <i class="icon-base ti tabler-edit me-1"></i>{{ __('admin.edit') }}
                                        </a>
                                        <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $method->id }})">
                                            <i class="icon-base ti tabler-toggle-left me-1"></i>
                                            {{ $method->is_active ? __('admin.deactivate') : __('admin.activate') }}
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $method->id }})">
                                            <i class="icon-base ti tabler-trash me-1"></i>{{ __('admin.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-body-secondary">{{ __('admin.shipping_methods_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($methods->hasPages())
            <div class="card-footer">{{ $methods->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
