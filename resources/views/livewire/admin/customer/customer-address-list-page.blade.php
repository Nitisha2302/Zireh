<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.customer_addresses') }}</h4>
                    <p class="mb-0 text-body-secondary">
                        {{ $customer->name }} — {{ $customer->phone }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-label-secondary">
                        <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.customers') }}
                    </a>
                    <a href="{{ route('admin.customers.addresses.create', $customer) }}" class="btn btn-primary">
                        <i class="icon-base ti tabler-plus me-1"></i> {{ __('admin.add_address') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.search_addresses_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-7 text-md-end">
                    <span class="badge bg-label-primary fs-6">{{ __('admin.total') }}: {{ $addresses->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>{{ __('admin.contact') }}</th>
                        <th>{{ __('admin.address') }}</th>
                        <th width="120">{{ __('admin.type') }}</th>
                        <th width="120">{{ __('admin.default') }}</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($addresses as $address)
                        <tr>
                            <td><span class="fw-semibold">#{{ $address->id }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $address->full_name }}</div>
                                <small class="text-body-secondary">{{ $address->phone }}</small>
                            </td>
                            <td>
                                <div>{{ $address->address_line_1 }}</div>
                                <small class="text-body-secondary">
                                    {{ collect([$address->address_line_2, $address->landmark, $address->city, $address->state, $address->country, $address->postal_code])->filter()->join(', ') }}
                                </small>
                            </td>
                            <td><span class="badge bg-label-secondary">{{ ucfirst($address->address_type) }}</span></td>
                            <td>
                                @if ($address->is_default)
                                    <span class="badge bg-label-success">{{ __('admin.default') }}</span>
                                @else
                                    <button type="button" class="btn btn-sm btn-label-primary" wire:click="setDefault({{ $address->id }})">{{ __('admin.make_default') }}</button>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.customers.addresses.edit', [$customer, $address]) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>{{ __('admin.edit') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $address->id }})">
                                                <i class="icon-base ti tabler-trash me-2"></i>{{ __('admin.delete') }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-5">
                                    <i class="icon-base ti tabler-map-pin" style="font-size: 60px"></i>
                                    <h5 class="mt-3">{{ __('admin.no_addresses_found') }}</h5>
                                    <p class="text-body-secondary mb-4">{{ __('admin.no_addresses_found_hint') }}</p>
                                    <a href="{{ route('admin.customers.addresses.create', $customer) }}" class="btn btn-primary">{{ __('admin.add_address') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($addresses->hasPages())
            <div class="card-footer">{{ $addresses->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
