<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{ route('warehouse.login') }}" class="app-brand-link">
                            <span class="app-brand-text demo text-heading fw-bold">{{ __('admin.warehouse_portal') }}</span>
                        </a>
                    </div>

                    <h4 class="mb-1">{{ __('admin.warehouse_login') }}</h4>
                    <p class="mb-6">{{ __('admin.warehouse_login_description') }}</p>

                    <form class="mb-4" wire:submit.prevent="authenticate">
                        <div class="mb-6 form-control-validation">
                            <label for="login" class="form-label">{{ __('admin.email_or_username') }}</label>
                            <input type="text" class="form-control @error('login') is-invalid @enderror"
                                wire:model.defer="login" placeholder="{{ __('admin.enter_email_or_username') }}" autofocus />
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6 form-password-toggle form-control-validation">
                            <label class="form-label" for="password">{{ __('admin.password') }}</label>
                            <div class="input-group input-group-merge">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    wire:model.defer="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                <span class="input-group-text cursor-pointer">
                                    <i class="icon-base ti tabler-eye-off"></i>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="my-8">
                            <div class="form-check mb-0 ms-2">
                                <input class="form-check-input" type="checkbox" id="remember-me" wire:model="remember" />
                                <label class="form-check-label" for="remember-me">{{ __('admin.remember_me') }}</label>
                            </div>
                        </div>

                        <div class="mb-6">
                            <button class="btn btn-primary d-grid w-100" type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ __('admin.login') }}</span>
                                <span wire:loading>{{ __('admin.signing_in') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
