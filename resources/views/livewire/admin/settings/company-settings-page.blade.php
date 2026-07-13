<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.company_settings') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.company_settings_description') }}</p>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.company_name') }}</label>
                        <input type="text"
                               class="form-control @error('company_name') is-invalid @enderror"
                               wire:model="company_name"
                               maxlength="120"
                               placeholder="{{ __('admin.company_name_placeholder') }}">
                        @error('company_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('admin.company_name_hint') }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.company_logo') }}</label>
                        <input type="file"
                               class="form-control @error('company_logo') is-invalid @enderror"
                               wire:model="company_logo"
                               accept="image/*">
                        @error('company_logo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('admin.company_logo_hint') }}</div>

                        <div class="mt-3" wire:loading wire:target="company_logo">
                            <span class="text-body-secondary">{{ __('admin.loading') }}...</span>
                        </div>

                        @if ($company_logo)
                            <div class="mt-3">
                                <img src="{{ $company_logo->temporaryUrl() }}"
                                     alt="{{ __('admin.company_logo') }}"
                                     class="rounded border"
                                     style="max-height: 72px; width: auto; object-fit: contain;">
                            </div>
                        @elseif ($existingLogoUrl)
                            <div class="mt-3 d-flex align-items-center gap-3">
                                <img src="{{ $existingLogoUrl }}"
                                     alt="{{ $company_name }}"
                                     class="rounded border"
                                     style="max-height: 72px; width: auto; object-fit: contain;">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="removeLogo"
                                        wire:confirm="{{ __('admin.company_logo_remove_confirm') }}">
                                    {{ __('admin.remove_logo') }}
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">{{ __('admin.save_company_settings') }}</span>
                            <span wire:loading wire:target="save">{{ __('admin.saving') }}...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
