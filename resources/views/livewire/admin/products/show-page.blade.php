<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-6">
        <div>
            <h4 class="mb-1">
                {{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) ?: __('admin.product_details') }}
            </h4>
            <p class="mb-0 text-body-secondary">
                {{ $product->restaurant?->name ?: __('admin.not_available') }} · {{ $product->category?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">
                <i class="icon-base ti tabler-edit me-1"></i>
                {{ __('admin.edit') }}
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">
                <i class="icon-base ti tabler-arrow-left me-1"></i>
                {{ __('admin.back') }}
            </a>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-lg-4">
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.product_images') }}</h5>
                </div>
                <div class="card-body">
                    @if ($product->images->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($product->images as $image)
                                <div class="col-6">
                                    <img
                                        src="{{ $image->image_url }}"
                                        class="img-fluid rounded border"
                                        alt="{{ __('admin.product_image') }}"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-body-secondary">
                            <i class="icon-base ti tabler-photo-off d-block mb-2" style="font-size: 48px;"></i>
                            {{ __('admin.no_product_images') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.product_summary') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-body-secondary">{{ __('admin.price') }}</dt>
                        <dd class="col-7">${{ number_format((float) $product->price, 2) }}</dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.discount_price') }}</dt>
                        <dd class="col-7">{{ $product->discount_price ? '$'.number_format((float) $product->discount_price, 2) : __('admin.not_available') }}</dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.food_type') }}</dt>
                        <dd class="col-7">{{ str($product->food_type)->replace('-', ' ')->title() }}</dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.prep_time') }}</dt>
                        <dd class="col-7">{{ $product->preparation_time_minutes }} {{ __('admin.minutes') }}</dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.stock') }}</dt>
                        <dd class="col-7">{{ $product->stock_quantity }}</dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.availability') }}</dt>
                        <dd class="col-7">
                            <span class="badge {{ $product->is_available ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ $product->is_available ? __('admin.available') : __('admin.unavailable') }}
                            </span>
                        </dd>

                        <dt class="col-5 text-body-secondary">{{ __('admin.status') }}</dt>
                        <dd class="col-7">{{ str($product->status)->title() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.product_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="mb-2">{{ __('admin.name') }}</h6>
                            <p class="mb-1"><strong>EN:</strong> {{ $product->getTranslation('name', 'en', false) ?: __('admin.not_available') }}</p>
                            <p class="mb-1"><strong>RU:</strong> {{ $product->getTranslation('name', 'ru', false) ?: __('admin.not_available') }}</p>
                            <p class="mb-0"><strong>TG:</strong> {{ $product->getTranslation('name', 'tg', false) ?: __('admin.not_available') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">{{ __('admin.category') }}</h6>
                            <p class="mb-1">{{ $product->category?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}</p>
                            <small class="text-body-secondary">{{ __('admin.cuisine') }}: {{ $product->category?->cuisine?->getTranslation('name', app()->getLocale(), false) ?: $product->category?->cuisine?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}</small>
                        </div>
                        <div class="col-12">
                            <h6 class="mb-2">{{ __('admin.description') }}</h6>
                            <p class="mb-1"><strong>EN:</strong> {{ $product->getTranslation('description', 'en', false) ?: __('admin.not_available') }}</p>
                            <p class="mb-1"><strong>RU:</strong> {{ $product->getTranslation('description', 'ru', false) ?: __('admin.not_available') }}</p>
                            <p class="mb-0"><strong>TG:</strong> {{ $product->getTranslation('description', 'tg', false) ?: __('admin.not_available') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-6 mb-6">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('admin.seller_details') }}</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>{{ __('admin.name') }}:</strong> {{ $product->seller?->full_name ?: __('admin.not_available') }}</p>
                            <p><strong>{{ __('admin.phone') }}:</strong> {{ $product->seller?->phone ?: __('admin.not_available') }}</p>
                            <p class="mb-0"><strong>{{ __('admin.email') }}:</strong> {{ $product->seller?->email ?: __('admin.not_available') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('admin.restaurant_details') }}</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>{{ __('admin.name') }}:</strong> {{ $product->restaurant?->name ?: __('admin.not_available') }}</p>
                            <p><strong>{{ __('admin.city') }}:</strong> {{ $product->restaurant?->city ?: __('admin.not_available') }}</p>
                            <p class="mb-0"><strong>{{ __('admin.status') }}:</strong> {{ $product->restaurant?->status ?: __('admin.not_available') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-6">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('admin.addons') }}</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('admin.name') }}</th>
                                        <th>{{ __('admin.price') }}</th>
                                        <th>{{ __('admin.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($product->addons as $addon)
                                        <tr>
                                            <td>{{ $addon->getTranslation('name', app()->getLocale(), false) ?: $addon->getTranslation('name', 'en', false) }}</td>
                                            <td>${{ number_format((float) $addon->price, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $addon->is_available ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    {{ $addon->is_available ? __('admin.available') : __('admin.unavailable') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-body-secondary">{{ __('admin.no_addons_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('admin.variants') }}</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('admin.name') }}</th>
                                        <th>{{ __('admin.price') }}</th>
                                        <th>{{ __('admin.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($product->variants as $variant)
                                        <tr>
                                            <td>{{ $variant->getTranslation('name', app()->getLocale(), false) ?: $variant->getTranslation('name', 'en', false) }}</td>
                                            <td>${{ number_format((float) $variant->price, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $variant->is_available ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    {{ $variant->is_available ? __('admin.available') : __('admin.unavailable') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-body-secondary">{{ __('admin.no_variants_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
