<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.elim_warehouse_address') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.elim_warehouse_address_description') }}</p>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.contact_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.phone') }}</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" wire:model="phone">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.mobile') }}</label>
                        <input type="text" class="form-control @error('mobile') is-invalid @enderror" wire:model="mobile">
                        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" wire:model="address">
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.province') }}</label>
                        <input type="text" class="form-control @error('province') is-invalid @enderror" wire:model="province">
                        @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.city') }}</label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror" wire:model="city">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.area') }}</label>
                        <input type="text" class="form-control @error('area') is-invalid @enderror" wire:model="area">
                        @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('admin.save_warehouse_address') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
