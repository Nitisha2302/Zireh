<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.products') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.products_description') }}</p>
                </div>
                <span class="badge bg-label-primary fs-6">
                    {{ __('admin.total') }}: {{ $products->total() }}
                </span>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="icon-base ti tabler-search"></i>
                        </span>
                        <input type="text" class="form-control"
                            placeholder="{{ __('admin.search_products_placeholder') }}"
                            wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <select class="form-select" wire:model.live="foodType">
                        <option value="">{{ __('admin.all_food_types') }}</option>
                        <option value="veg">{{ __('admin.veg') }}</option>
                        <option value="non-veg">{{ __('admin.non_veg') }}</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <select class="form-select" wire:model.live="availability">
                        <option value="">{{ __('admin.all_availability') }}</option>
                        <option value="available">{{ __('admin.available') }}</option>
                        <option value="unavailable">{{ __('admin.unavailable') }}</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select class="form-select" wire:model.live="status">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        <option value="active">{{ __('admin.active') }}</option>
                        <option value="inactive">{{ __('admin.inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="90">{{ __('admin.image') }}</th>
                        <th>{{ __('admin.product') }}</th>
                        <th>{{ __('admin.seller') }}</th>
                        <th>{{ __('admin.restaurant') }}</th>
                        <th>{{ __('admin.category') }}</th>
                        <th width="150">{{ __('admin.price') }}</th>
                        <th width="130">{{ __('admin.stock') }}</th>
                        <th width="130">{{ __('admin.status') }}</th>
                        <th width="170"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    @php($image = $product->images->first())
                        <tr>
                            <td>
                                {{-- {{ dd($image) }} --}}
                                @if ($image)
                                    <img src="{{ $image->image_url }}" class="rounded border object-fit-cover"
                                        style="width: 60px; height: 60px;"
                                        alt="{{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) }}">
                                @else
                                    <div class="d-flex justify-content-center align-items-center bg-light rounded border"
                                        style="width: 60px; height: 60px;">
                                        <i class="icon-base ti tabler-photo-off"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                                </div>
                                <small class="text-body-secondary">
                                    {{ str($product->food_type)->replace('-', ' ')->title() }} ·
                                    {{ $product->preparation_time_minutes }} {{ __('admin.minutes') }}
                                </small>
                            </td>
                            <td>
                                <div>{{ $product->seller?->full_name ?: __('admin.not_available') }}</div>
                                <small class="text-body-secondary">{{ $product->seller?->phone }}</small>
                            </td>
                            <td>
                                <div>{{ $product->restaurant?->name ?: __('admin.not_available') }}</div>
                                <small class="text-body-secondary">{{ $product->restaurant?->city }}</small>
                            </td>
                            <td>
                                <div>
                                    {{ $product->category?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                                </div>
                                <small
                                    class="text-body-secondary">{{ $product->category?->cuisine?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->cuisine?->getTranslation('name', 'en', false) }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">${{ number_format((float) $product->price, 2) }}</div>
                                @if ($product->discount_price)
                                    <small
                                        class="text-success">${{ number_format((float) $product->discount_price, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">{{ $product->stock_quantity }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span
                                        class="badge {{ $product->is_available ? 'bg-label-success' : 'bg-label-danger' }}">
                                        {{ $product->is_available ? __('admin.available') : __('admin.unavailable') }}
                                    </span>
                                    <small class="text-body-secondary">{{ str($product->status)->title() }}</small>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.products.show', $product) }}"
                                        class="btn btn-sm btn-label-primary">
                                        <i class="icon-base ti tabler-eye me-1"></i>
                                        {{ __('admin.view') }}
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="icon-base ti tabler-edit me-1"></i>
                                        {{ __('admin.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="icon-base ti tabler-basket" style="font-size: 64px;"></i>
                                    </div>
                                    <h5 class="mb-2">{{ __('admin.no_products_found') }}</h5>
                                    <p class="text-body-secondary mb-0">{{ __('admin.no_products_found_description') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">
                {{ $products->links('livewire::bootstrap') }}
            </div>
        @endif
    </div>
</div>
