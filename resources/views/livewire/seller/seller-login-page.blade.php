<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{ route('seller.login') }}" class="app-brand-link">
                            <span class="app-brand-text demo text-heading fw-bold">FDB Sellers</span>
                        </a>
                    </div>

                    <h4 class="mb-1">{{ __('seller.seller_login') }}</h4>
                    <p class="mb-6">{{ __('seller.seller_login_description') }}</p>

                    {{-- Flash Messages --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Tab Navigation --}}
                    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link {{ $activeTab === 'otp' ? 'active' : '' }}" 
                                    wire:click="$set('activeTab', 'otp')" 
                                    type="button" 
                                    role="tab">
                                <i class="icon-base ti tabler-lock-open me-2"></i>
                                {{ __('seller.login_with_otp') }}
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}" 
                                    wire:click="$set('activeTab', 'password')" 
                                    type="button" 
                                    role="tab">
                                <i class="icon-base ti tabler-key me-2"></i>
                                {{ __('seller.login_with_password') }}
                            </button>
                        </li>
                    </ul>

                    {{-- OTP Login Form --}}
                    @if ($activeTab === 'otp')
                        <form wire:submit.prevent="verifyOtp" class="mb-4">
                            <div class="mb-6 form-control-validation">
                                <label for="phone_number" class="form-label">{{ __('seller.phone_number') }}</label>
                                <input type="tel" 
                                       class="form-control @error('phone_number') is-invalid @enderror"
                                       wire:model.defer="phone_number" 
                                       placeholder="{{ __('seller.enter_phone_number') }}" 
                                       autofocus />
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('seller.phone_number_hint') }}</small>
                            </div>

                            {{-- Send OTP Button --}}
                            @if (!$otp_sent)
                                <div class="mb-6">
                                    <button class="btn btn-primary d-grid w-100" 
                                            type="button" 
                                            wire:click="sendOtp"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove>{{ __('seller.send_otp') }}</span>
                                        <span wire:loading>{{ __('seller.sending_otp') }}</span>
                                    </button>
                                </div>
                            @endif

                            {{-- OTP Input --}}
                            @if ($otp_sent)
                                <div class="mb-6 form-control-validation">
                                    <label for="otp" class="form-label">{{ __('seller.enter_otp') }}</label>
                                    <input type="text" 
                                           class="form-control @error('otp') is-invalid @enderror"
                                           wire:model.defer="otp" 
                                           placeholder="000000" 
                                           maxlength="6" />
                                    @error('otp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        {{ __('seller.otp_sent_to') }} +{{ $phone_number }}
                                    </small>
                                </div>

                                <div class="mb-6">
                                    <button class="btn btn-primary d-grid w-100" type="submit" wire:loading.attr="disabled">
                                        <span wire:loading.remove>{{ __('seller.verify_otp') }}</span>
                                        <span wire:loading>{{ __('seller.verifying') }}</span>
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <button type="button" 
                                            class="btn btn-link btn-sm" 
                                            wire:click="$set('otp_sent', false)">
                                        {{ __('seller.use_different_number') }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    @endif

                    {{-- Password Login Form --}}
                    @if ($activeTab === 'password')
                        <form wire:submit.prevent="loginWithPassword" class="mb-4">
                            <div class="mb-6 form-control-validation">
                                <label for="email" class="form-label">{{ __('seller.email') }}</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror"
                                       wire:model.defer="email" 
                                       placeholder="{{ __('seller.enter_email') }}" 
                                       autofocus />
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-6 form-password-toggle form-control-validation">
                                <label class="form-label" for="password">{{ __('seller.password') }}</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror"
                                           wire:model.defer="password"
                                           placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                           aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="my-8">
                                <div class="form-check mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" id="remember-me"
                                        wire:model="remember" />
                                    <label class="form-check-label" for="remember-me">{{ __('seller.remember_me') }}</label>
                                </div>
                            </div>

                            <div class="mb-6">
                                <button class="btn btn-primary d-grid w-100" type="submit" wire:loading.attr="disabled">
                                    <span wire:loading.remove>{{ __('seller.login') }}</span>
                                    <span wire:loading>{{ __('seller.logging_in') }}</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Delete Account Button --}}
                    <div class="text-center">
                        <button type="button" 
                                class="btn btn-link btn-sm text-danger" 
                                wire:click="openDeleteModal">
                            <i class="icon-base ti tabler-trash me-1"></i>
                            {{ __('seller.delete_account') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
@if ($show_delete_modal)
    <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('seller.delete_account') }}</h5>
                    <button type="button" class="btn-close" wire:click="closeDeleteModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ __('seller.warning') }}</strong>
                        {{ __('seller.delete_account_warning') }}
                    </div>

                    <form wire:submit.prevent="confirmDelete">
                        <div class="mb-4">
                            <p class="text-muted">{{ __('seller.delete_account_confirmation') }}</p>
                        </div>

                        <div class="mb-4 form-control-validation">
                            <label class="form-label" for="delete_password">{{ __('seller.confirm_password') }}</label>
                            <div class="input-group input-group-merge">
                                <input type="password" 
                                       class="form-control @error('delete_password') is-invalid @enderror"
                                       wire:model.defer="delete_password"
                                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                       aria-describedby="delete_password" />
                                <span class="input-group-text cursor-pointer">
                                    <i class="icon-base ti tabler-eye-off"></i>
                                </span>
                                @error('delete_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input @error('delete_confirmed') is-invalid @enderror" 
                                   type="checkbox" 
                                   id="confirm-delete"
                                   wire:model="delete_confirmed" />
                            <label class="form-check-label" for="confirm-delete">
                                {{ __('seller.confirm_permanent_deletion') }}
                            </label>
                            @error('delete_confirmed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                                {{ __('seller.cancel') }}
                            </button>
                            <button type="submit" class="btn btn-danger" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ __('seller.delete_permanently') }}</span>
                                <span wire:loading>{{ __('seller.deleting') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
