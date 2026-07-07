<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $isEdit ? __('admin.edit_warehouse_staff') : __('admin.add_warehouse_staff') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.warehouse_staff_form_description') }}</p>
        </div>
        <a href="{{ route('admin.warehouse-staff.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form wire:submit="save" class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.name') }}</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.username') }}</label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" wire:model="username">
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.email') }}</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.role') }}</label>
                    <select class="form-select @error('role') is-invalid @enderror" wire:model.live="role">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if ($role === \App\Models\Admin::ROLE_TAJIKISTAN_WAREHOUSE)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.warehouse') }}</label>
                        <select class="form-select @error('warehouse_id') is-invalid @enderror" wire:model="warehouse_id">
                            <option value="">{{ __('admin.select_warehouse') }}</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">{{ $isEdit ? __('admin.new_password') : __('admin.password') }}</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" @if($isEdit) placeholder="{{ __('admin.leave_blank_to_keep') }}" @endif>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.confirm_password') }}</label>
                    <input type="password" class="form-control" wire:model="password_confirmation">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $isEdit ? __('admin.update') : __('admin.create') }}</span>
                        <span wire:loading>{{ __('admin.saving') }}...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
