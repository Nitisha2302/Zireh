<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.warehouse_basic_info') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_basic_info_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="warehouse_name">{{ __('admin.warehouse_name') }}</label>
                        <input id="warehouse_name" type="text" wire:model.blur="warehouse_name" class="form-control @error('warehouse_name') is-invalid @enderror">
                        @error('warehouse_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">{{ __('admin.contact_number') }}</label>
                        <input id="contact_number" type="text" wire:model.blur="contact_number" class="form-control @error('contact_number') is-invalid @enderror">
                        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">{{ __('admin.full_address') }}</label>
                        <textarea id="address" rows="3" wire:model.blur="address" class="form-control @error('address') is-invalid @enderror"></textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.warehouse_panel_login') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_panel_login_hint') }}</small>
            </div>
            <div class="card-body">
                @php $tajikistanLoginUrl = route('tajikistan.login'); @endphp
                <div class="alert alert-primary d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4" x-data="{ copied: false }">
                    <div>
                        <div class="fw-semibold">{{ __('admin.tajikistan_warehouse_login_url') }}</div>
                        <code class="small">{{ $tajikistanLoginUrl }}</code>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary text-nowrap"
                        @click="navigator.clipboard.writeText(@js($tajikistanLoginUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    >
                        <i class="icon-base ti tabler-copy me-1"></i>
                        <span x-text="copied ? @js(__('admin.copied')) : @js(__('admin.copy_login_url'))"></span>
                    </button>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="login_username">{{ __('admin.login_username') }}</label>
                        <input id="login_username" type="text" wire:model.blur="login_username" class="form-control @error('login_username') is-invalid @enderror">
                        @error('login_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_email">{{ __('admin.login_email') }}</label>
                        <input id="login_email" type="email" wire:model.blur="login_email" class="form-control @error('login_email') is-invalid @enderror">
                        @error('login_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_password">{{ ($isEdit ?? false) ? __('admin.new_password') : __('admin.password') }}</label>
                        <input id="login_password" type="password" wire:model="login_password" class="form-control @error('login_password') is-invalid @enderror" @if($isEdit ?? false) placeholder="{{ __('admin.leave_blank_to_keep') }}" @endif>
                        @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_password_confirmation">{{ __('admin.confirm_password') }}</label>
                        <input id="login_password_confirmation" type="password" wire:model="login_password_confirmation" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.working_hours') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_working_hours_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    @foreach ($workingHours as $day => $hours)
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="fw-semibold">{{ __('admin.weekday_'.$hours['day_of_week']) }}</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="closed-{{ $day }}"
                                        wire:model.live="workingHours.{{ $day }}.is_closed">
                                    <label class="form-check-label" for="closed-{{ $day }}">{{ __('admin.closed') }}</label>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-sm-3">
                                    <label class="form-label small" for="opens-{{ $day }}">{{ __('admin.opening_time') }}</label>
                                    <input id="opens-{{ $day }}" type="time"
                                        class="form-control @error('workingHours.'.$day.'.opens_at') is-invalid @enderror"
                                        wire:model="workingHours.{{ $day }}.opens_at"
                                        @disabled($hours['is_closed'])>
                                    @error('workingHours.'.$day.'.opens_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small" for="closes-{{ $day }}">{{ __('admin.closing_time') }}</label>
                                    <input id="closes-{{ $day }}" type="time"
                                        class="form-control @error('workingHours.'.$day.'.closes_at') is-invalid @enderror"
                                        wire:model="workingHours.{{ $day }}.closes_at"
                                        @disabled($hours['is_closed'])>
                                    @error('workingHours.'.$day.'.closes_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small" for="break-start-{{ $day }}">{{ __('admin.break_starts_at') }}</label>
                                    <input id="break-start-{{ $day }}" type="time"
                                        class="form-control @error('workingHours.'.$day.'.break_starts_at') is-invalid @enderror"
                                        wire:model="workingHours.{{ $day }}.break_starts_at"
                                        @disabled($hours['is_closed'])>
                                    @error('workingHours.'.$day.'.break_starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small" for="break-end-{{ $day }}">{{ __('admin.break_ends_at') }}</label>
                                    <input id="break-end-{{ $day }}" type="time"
                                        class="form-control @error('workingHours.'.$day.'.break_ends_at') is-invalid @enderror"
                                        wire:model="workingHours.{{ $day }}.break_ends_at"
                                        @disabled($hours['is_closed'])>
                                    @error('workingHours.'.$day.'.break_ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="status">{{ __('admin.status') }}</label>
                    <select id="status" wire:model="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ $submitLabel }}
                </button>
                <a href="{{ $cancelUrl }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
