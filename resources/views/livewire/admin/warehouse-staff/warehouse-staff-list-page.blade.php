<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.warehouse_staff') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.warehouse_staff_description') }}</p>
        </div>
        <a href="{{ route('admin.warehouse-staff.create') }}" class="btn btn-primary">
            <i class="icon-base ti tabler-plus me-1"></i>
            {{ __('admin.add_warehouse_staff') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.warehouse_staff_search_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="roleFilter">
                        <option value="">{{ __('admin.all_roles') }}</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>{{ __('admin.name') }}</th>
                        <th>{{ __('admin.username') }}</th>
                        <th>{{ __('admin.email') }}</th>
                        <th>{{ __('admin.role') }}</th>
                        <th>{{ __('admin.warehouse') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td class="fw-medium">{{ $member->name }}</td>
                            <td>{{ $member->username }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $roles[$member->role] ?? $member->role }}</td>
                            <td>{{ $member->warehouse?->warehouse_name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.warehouse-staff.edit', $member) }}" class="btn btn-sm btn-icon btn-text-secondary">
                                    <i class="icon-base ti tabler-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-icon btn-text-danger" wire:click="delete({{ $member->id }})">
                                    <i class="icon-base ti tabler-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-body-secondary">{{ __('admin.warehouse_staff_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($staff->hasPages())
            <div class="card-footer">{{ $staff->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
