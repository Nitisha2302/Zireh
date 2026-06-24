<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.privacy_terms_settings') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.privacy_terms_settings_description') }}</p>
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
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.privacy_policy') }}</label>
                        <textarea class="form-control @error('privacy_policy') is-invalid @enderror"
                                  wire:model="privacy_policy"
                                  rows="12"
                                  placeholder="{{ __('admin.privacy_policy_placeholder') }}"></textarea>
                        @error('privacy_policy')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('admin.terms_conditions') }}</label>
                        <textarea class="form-control @error('terms_conditions') is-invalid @enderror"
                                  wire:model="terms_conditions"
                                  rows="12"
                                  placeholder="{{ __('admin.terms_conditions_placeholder') }}"></textarea>
                        @error('terms_conditions')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Delete Account Information</label>
                        <textarea class="form-control @error('delete_account') is-invalid @enderror"
                                  wire:model="delete_account"
                                  rows="12"
                                  placeholder="Enter delete account information..."></textarea>
                        @error('delete_account')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            {{ __('admin.save_privacy_terms_settings') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
