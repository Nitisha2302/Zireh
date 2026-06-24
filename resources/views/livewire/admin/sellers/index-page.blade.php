<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ __('admin.sellers') }}</h4>
                <p class="mb-0 text-body-secondary">{{ __('admin.sellers_description') }}</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('admin.owner') }}</th>
                        <th>{{ __('admin.restaurant') }}</th>
                        <th>{{ __('admin.contact') }}</th>
                        {{-- <th>{{ __('admin.documents') }}</th> --}}
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.verification') }}</th>
                        <th class="text-center"> {{ __('admin.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sellers as $seller)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $seller->full_name }}</div>
                                <small class="text-body-secondary">{{ $seller->email }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $seller->restaurant?->name }}</div>
                                <small class="text-body-secondary">{{ $seller->restaurant?->city }}</small>
                            </td>
                            <td>
                                <div>{{ $seller->phone }}</div>
                                <small class="text-body-secondary">{{ $seller->restaurant?->phone }}</small>
                            </td>
                            {{-- <td>{{ $seller->documents->count() }}</td> --}}
                            <td><span class="badge bg-label-primary">{{ $seller->status }}</span></td>
                            <td><span class="badge bg-label-warning">{{ $seller->verification_status }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.sellers.restaurant', $seller) }}"
                                        class="btn btn-sm btn-label-info">
                                        <i class="icon-base ti tabler-building-store me-1"></i>
                                        {{ __('admin.restaurant_view') }}
                                    </a>
                                    <a href="{{ route('admin.sellers.show', $seller) }}"
                                        class="btn btn-sm btn-primary">
                                        {{ __('admin.review') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary">
                                {{ __('admin.no_sellers_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
