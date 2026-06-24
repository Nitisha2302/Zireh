<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-6">
        <div>
            <h4 class="mb-1">{{ __('admin.my_profile') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.profile_description') }}</p>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar avatar-xl me-4">
                            <img src="{{ $admin->avatar ?? '' }}" alt="Admin avatar" class="rounded-circle" />
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $admin->name }}</h5>
                            <span class="badge bg-label-primary">{{ $admin->username }}</span>
                        </div>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-4 text-body-secondary">{{ __('admin.email') }}</dt>
                        <dd class="col-8">{{ $admin->email }}</dd>

                        <dt class="col-4 text-body-secondary">{{ __('admin.phone') }}</dt>
                        <dd class="col-8">{{ $admin->phone ?: __('admin.not_added') }}</dd>

                        <dt class="col-4 text-body-secondary">{{ __('admin.two_factor') }}</dt>
                        <dd class="col-8">{{ $admin->is_two_factor ? __('admin.enabled') : __('admin.disabled') }}</dd>

                        <dt class="col-4 text-body-secondary">{{ __('admin.verified') }}</dt>
                        <dd class="col-8">{{ $admin->email_verified_at?->format('d M Y, h:i A') ?: __('admin.pending') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.current_login_sessions') }}</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('admin.ip_address') }}</th>
                                <th>{{ __('admin.last_page') }}</th>
                                <th>{{ __('admin.login_time') }}</th>
                                <th>{{ __('admin.last_seen') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse ($currentSessions as $session)
                                <tr>
                                    <td>{{ $session->ip_address ?: '-' }}</td>
                                    <td class="text-truncate" style="max-width: 260px;">{{ $session->last_activity_url ?: '-' }}</td>
                                    <td>{{ $session->login_at?->format('d M Y, h:i A') ?: '-' }}</td>
                                    <td>{{ $session->last_seen_at?->diffForHumans() ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-body-secondary">{{ __('admin.no_active_sessions') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.recent_login_logs') }}</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('admin.status') }}</th>
                                <th>{{ __('admin.login') }}</th>
                                <th>{{ __('admin.ip_address') }}</th>
                                <th>{{ __('admin.login_time') }}</th>
                                <th>{{ __('admin.logout_time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse ($loginLogs as $log)
                                <tr>
                                    <td>
                                        @if ($log->successful)
                                            <span class="badge bg-label-success">{{ __('admin.success') }}</span>
                                        @else
                                            <span class="badge bg-label-danger">{{ __('admin.failed') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->login ?: '-' }}</td>
                                    <td>{{ $log->ip_address ?: '-' }}</td>
                                    <td>{{ $log->login_at?->format('d M Y, h:i A') ?: '-' }}</td>
                                    <td>{{ $log->logout_at?->format('d M Y, h:i A') ?: __('admin.active') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary">{{ __('admin.no_login_logs') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
