<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.warehouse_list') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.warehouse_list_description') }}</p>
        </div>
        <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
            <i class="icon-base ti tabler-plus me-1"></i>
            {{ __('admin.add_warehouse') }}
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-building-warehouse"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.warehouse_stats_total') }}</p>
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
                        <p class="mb-0 text-body-secondary small">{{ __('admin.warehouse_stats_active') }}</p>
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
                        <p class="mb-0 text-body-secondary small">{{ __('admin.warehouse_stats_inactive') }}</p>
                        <h4 class="mb-0">{{ $stats['inactive'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card position-relative">
        <div class="card-body border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label mb-1">{{ __('admin.search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.warehouse_search_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label mb-1">{{ __('admin.status') }}</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
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
                        {{ __('admin.showing_results', ['count' => $warehouses->total()]) }}
                    </span>
                </div>
            </div>

            @if ($this->hasActiveFilters())
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if ($search !== '')
                        <span class="badge bg-label-secondary">
                            {{ __('admin.search') }}: {{ $search }}
                        </span>
                    @endif
                    @if ($statusFilter !== '')
                        <span class="badge bg-label-secondary">
                            {{ __('admin.status') }}: {{ $statuses[$statusFilter] ?? $statusFilter }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <div class="table-responsive position-relative" wire:loading.class="opacity-50">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="280">
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('warehouse_name')">
                                {{ __('admin.warehouse') }}
                                @if ($sortField === 'warehouse_name')
                                    <i class="icon-base ti tabler-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </button>
                        </th>
                        <th>
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('city')">
                                {{ __('admin.location') }}
                                @if ($sortField === 'city')
                                    <i class="icon-base ti tabler-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </button>
                        </th>
                        <th width="180">{{ __('admin.coordinates') }}</th>
                        <th width="200">{{ __('admin.contact') }}</th>
                        <th width="110">
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('status')">
                                {{ __('admin.status') }}
                                @if ($sortField === 'status')
                                    <i class="icon-base ti tabler-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </button>
                        </th>
                        <th width="150">
                            <button type="button" class="btn btn-sm btn-link p-0 text-body fw-semibold text-decoration-none" wire:click="sortBy('created_at')">
                                {{ __('admin.created_date') }}
                                @if ($sortField === 'created_at')
                                    <i class="icon-base ti tabler-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </button>
                        </th>
                        <th width="120" class="text-end">{{ __('admin.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-sm flex-shrink-0">
                                        <span class="avatar-initial rounded {{ $warehouse->isActive() ? 'bg-label-primary' : 'bg-label-secondary' }}">
                                            <i class="icon-base ti tabler-building-warehouse"></i>
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="fw-semibold text-body text-decoration-none d-block text-truncate">
                                            {{ $warehouse->warehouse_name }}
                                        </a>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <code class="small">{{ $warehouse->warehouse_code }}</code>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="icon-base ti tabler-map-pin text-primary mt-1"></i>
                                    <div>
                                        <div class="fw-medium">{{ $warehouse->city }}</div>
                                        <small class="text-body-secondary d-block">{{ $warehouse->state }}</small>
                                        <span class="badge bg-label-info mt-1">{{ $warehouse->country }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><span class="text-body-secondary">{{ __('admin.latitude') }}:</span> {{ $warehouse->latitude }}</div>
                                    <div><span class="text-body-secondary">{{ __('admin.longitude') }}:</span> {{ $warehouse->longitude }}</div>
                                </div>
                                <a href="https://www.google.com/maps?q={{ $warehouse->latitude }},{{ $warehouse->longitude }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-label-primary mt-2">
                                    <i class="icon-base ti tabler-external-link me-1"></i>
                                    {{ __('admin.view_on_map') }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $warehouse->contact_person }}</div>
                                <a href="tel:{{ $warehouse->contact_number }}" class="small text-body-secondary text-decoration-none">
                                    <i class="icon-base ti tabler-phone me-1"></i>{{ $warehouse->contact_number }}
                                </a>
                                @if ($warehouse->email)
                                    <div class="small text-body-secondary text-truncate mt-1" title="{{ $warehouse->email }}">
                                        <i class="icon-base ti tabler-mail me-1"></i>{{ $warehouse->email }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($warehouse->isActive())
                                    <span class="badge bg-label-success">
                                        <i class="icon-base ti tabler-circle-check me-1"></i>{{ __('admin.active') }}
                                    </span>
                                @else
                                    <span class="badge bg-label-danger">
                                        <i class="icon-base ti tabler-circle-x me-1"></i>{{ __('admin.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $warehouse->created_at->format('d M Y') }}</div>
                                <small class="text-body-secondary">{{ $warehouse->created_at->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-sm btn-icon btn-text-secondary rounded" title="{{ __('admin.view') }}">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-icon btn-text-secondary rounded" title="{{ __('admin.edit') }}">
                                        <i class="icon-base ti tabler-pencil"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-text-secondary rounded" data-bs-toggle="dropdown" title="{{ __('admin.more_actions') }}">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $warehouse->id }})">
                                                    <i class="icon-base ti tabler-refresh me-2"></i>
                                                    {{ $warehouse->isActive() ? __('admin.deactivate') : __('admin.activate') }}
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $warehouse->id }})">
                                                    <i class="icon-base ti tabler-trash me-2"></i>{{ __('admin.delete') }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-0">
                                <div class="text-center py-5 px-3">
                                    <div class="avatar avatar-xl mx-auto mb-3">
                                        <span class="avatar-initial rounded-circle bg-label-secondary">
                                            <i class="icon-base ti tabler-building-warehouse" style="font-size: 2rem;"></i>
                                        </span>
                                    </div>
                                    <h5 class="mb-2">{{ __('admin.no_warehouses_found') }}</h5>
                                    <p class="text-body-secondary mb-4 mx-auto" style="max-width: 420px;">
                                        {{ $this->hasActiveFilters() ? __('admin.no_warehouses_filtered_hint') : __('admin.no_warehouses_found_hint') }}
                                    </p>
                                    @if ($this->hasActiveFilters())
                                        <button type="button" class="btn btn-label-secondary me-2" wire:click="clearFilters">
                                            {{ __('admin.clear_filters') }}
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
                                        <i class="icon-base ti tabler-plus me-1"></i>
                                        {{ __('admin.add_warehouse') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div wire:loading.flex class="position-absolute top-50 start-50 translate-middle" style="z-index: 2;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __('admin.loading') }}</span>
            </div>
        </div>

        @if ($warehouses->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-body-secondary">
                    {{ __('admin.pagination_summary', ['from' => $warehouses->firstItem(), 'to' => $warehouses->lastItem(), 'total' => $warehouses->total()]) }}
                </small>
                {{ $warehouses->links('livewire::bootstrap') }}
            </div>
        @endif
    </div>
</div>
