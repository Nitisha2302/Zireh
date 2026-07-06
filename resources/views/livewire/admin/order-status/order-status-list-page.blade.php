<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.order_statuses') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.order_statuses_description') }}</p>
        </div>
        @if ($view === 'active')
            <a href="{{ route('admin.order-statuses.create') }}" class="btn btn-primary">
                <i class="icon-base ti tabler-plus me-1"></i>{{ __('admin.add_order_status') }}
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn {{ $view === 'active' ? 'btn-primary' : 'btn-label-secondary' }}" wire:click="$set('view', 'active')">
                    {{ __('admin.order_status_active_list') }}
                </button>
                <button type="button" class="btn {{ $view === 'trash' ? 'btn-primary' : 'btn-label-secondary' }}" wire:click="$set('view', 'trash')">
                    <i class="icon-base ti tabler-trash me-1"></i>{{ __('admin.order_status_trash') }}
                </button>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label mb-1">{{ __('admin.search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.order_status_search_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                @if ($view === 'active')
                    <div class="col-lg-3">
                        <label class="form-label mb-1">{{ __('admin.status') }}</label>
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">{{ __('admin.all_statuses') }}</option>
                            <option value="active">{{ __('admin.active') }}</option>
                            <option value="inactive">{{ __('admin.inactive') }}</option>
                        </select>
                    </div>
                @endif
                <div class="col-lg-3 text-lg-end">
                    <span class="badge bg-label-primary fs-6">{{ $statuses->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive position-relative" wire:loading.class="opacity-50">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin.order_status_name') }}</th>
                        <th>{{ __('admin.order_status_code') }}</th>
                        <th>{{ __('admin.order_status_color') }}</th>
                        <th>{{ __('admin.order_status_sort_order') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.type') }}</th>
                        <th>{{ __('admin.created_date') }}</th>
                        <th class="text-end">{{ __('admin.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statuses as $status)
                        <tr>
                            <td class="fw-semibold">{{ $status->name }}</td>
                            <td><code>{{ $status->code }}</code></td>
                            <td><span class="badge {{ $status->badgeClass() }}">{{ ucfirst($status->color) }}</span></td>
                            <td>{{ $status->sort_order }}</td>
                            <td>
                                @if ($view === 'active')
                                    <span class="badge {{ $status->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                        {{ $status->is_active ? __('admin.active') : __('admin.inactive') }}
                                    </span>
                                @else
                                    <span class="badge bg-label-danger">{{ __('admin.order_status_trash') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($status->is_system)
                                    <span class="badge bg-label-info">{{ __('admin.order_status_system') }}</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ __('admin.order_status_custom') }}</span>
                                @endif
                            </td>
                            <td>{{ $status->created_at?->format('M d, Y') }}</td>
                            <td class="text-end">
                                @if ($view === 'active')
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.order-statuses.edit', $status) }}">
                                                <i class="icon-base ti tabler-edit me-1"></i>{{ __('admin.edit') }}
                                            </a>
                                            <button type="button" class="dropdown-item" wire:click="toggleActive({{ $status->id }})">
                                                <i class="icon-base ti tabler-toggle-left me-1"></i>
                                                {{ $status->is_active ? __('admin.deactivate') : __('admin.activate') }}
                                            </button>
                                            @unless ($status->is_system)
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $status->id }})">
                                                    <i class="icon-base ti tabler-trash me-1"></i>{{ __('admin.move_to_trash') }}
                                                </button>
                                            @endunless
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="btn btn-sm btn-label-success" wire:click="restore({{ $status->id }})">
                                            {{ __('admin.restore') }}
                                        </button>
                                        @unless ($status->is_system)
                                            <button type="button" class="btn btn-sm btn-label-danger" wire:click="confirmForceDelete({{ $status->id }})">
                                                {{ __('admin.permanent_delete') }}
                                            </button>
                                        @endunless
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-body-secondary">
                                {{ $view === 'trash' ? __('admin.order_status_trash_empty') : __('admin.order_statuses_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($statuses->hasPages())
            <div class="card-footer">{{ $statuses->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
