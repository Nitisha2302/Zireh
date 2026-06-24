<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">{{ __('admin.products') }}</h5>
                <p class="mb-0 text-body-secondary">{{ __('admin.restaurant_products_description') }}</p>
            </div>
            <span class="badge bg-label-primary">{{ __('admin.total') }}: {{ $products->total() }}</span>
        </div>
    </div>
    <div class="card-body border-top border-bottom">
        <div class="input-group">
            <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
            <input type="text" class="form-control" placeholder="{{ __('admin.search_products_placeholder') }}"
                wire:model.live.debounce.500ms="search">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('admin.product') }}</th>
                    <th>{{ __('admin.category') }}</th>
                    <th>{{ __('admin.price') }}</th>
                    <th>{{ __('admin.stock') }}</th>
                    <th>{{ __('admin.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php($image = $product->images->first())
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($image)
                                    <img src="{{ app(\App\Services\FileManager::class)->url($image->image_path) }}"
                                        alt="{{ __('admin.product_image') }}" class="rounded border object-fit-cover"
                                        style="width: 52px; height: 52px;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded border"
                                        style="width: 52px; height: 52px;">
                                        <i class="icon-base ti tabler-photo-off"></i>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.products.show', $product) }}" class="fw-semibold">
                                        {{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                                    </a>
                                    <small class="d-block text-body-secondary">
                                        {{ str($product->food_type)->replace('-', ' ')->title() }} |
                                        {{ $product->preparation_time_minutes }} {{ __('admin.minutes') }}
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                {{ $product->category?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                            </div>
                            <small class="text-body-secondary">
                                {{ $product->category?->cuisine?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->cuisine?->getTranslation('name', 'en', false) }}
                            </small>
                        </td>
                        <td>
                            <span class="fw-semibold">${{ number_format((float) $product->price, 2) }}</span>
                            @if ($product->discount_price)
                                <small class="d-block text-success">${{ number_format((float) $product->discount_price, 2) }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-label-secondary">{{ $product->stock_quantity }}</span></td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge {{ $product->is_available ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $product->is_available ? __('admin.available') : __('admin.unavailable') }}
                                </span>
                                <small class="text-body-secondary">{{ str($product->status)->title() }}</small>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-5">{{ __('admin.no_products_found') }}</td>
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
