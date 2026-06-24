<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="save">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-6">
            <div>
                <h4 class="mb-1">{{ __('admin.edit_product') }}</h4>
                <p class="mb-0 text-body-secondary">
                    {{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) ?: __('admin.product') }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-label-primary">
                    <i class="icon-base ti tabler-eye me-1"></i>
                    {{ __('admin.view') }}
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-label-secondary">
                    <i class="icon-base ti tabler-arrow-left me-1"></i>
                    {{ __('admin.back') }}
                </a>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-lg-8">
                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.product_information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.category') }}</label>
                                <select wire:model="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">{{ __('admin.select_category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->getTranslation('name', app()->getLocale(), false) ?: $category->getTranslation('name', 'en', false) }}
                                            @if ($category->cuisine)
                                                ({{ $category->cuisine->getTranslation('name', app()->getLocale(), false) ?: $category->cuisine->getTranslation('name', 'en', false) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.food_type') }}</label>
                                <select wire:model="food_type" class="form-select @error('food_type') is-invalid @enderror">
                                    <option value="veg">{{ __('admin.veg') }}</option>
                                    <option value="non-veg">{{ __('admin.non_veg') }}</option>
                                </select>
                                @error('food_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.price') }}</label>
                                <input type="number" step="0.01" min="0" wire:model.blur="price" class="form-control @error('price') is-invalid @enderror">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.discount_price') }}</label>
                                <input type="number" step="0.01" min="0" wire:model.blur="discount_price" class="form-control @error('discount_price') is-invalid @enderror">
                                @error('discount_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.prep_time') }}</label>
                                <input type="number" min="1" wire:model.blur="preparation_time_minutes" class="form-control @error('preparation_time_minutes') is-invalid @enderror">
                                @error('preparation_time_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.stock') }}</label>
                                <input type="number" min="0" wire:model.blur="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror">
                                @error('stock_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.status') }}</label>
                                <select wire:model="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="active">{{ __('admin.active') }}</option>
                                    <option value="inactive">{{ __('admin.inactive') }}</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_available" wire:model="is_available">
                                    <label class="form-check-label" for="is_available">
                                        {{ __('admin.available') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.product_translations') }}</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#product-lang-en">
                                    {{ __('admin.english') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#product-lang-ru">
                                    {{ __('admin.russian') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#product-lang-tg">
                                    {{ __('admin.tajik') }}
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
                                <div class="tab-pane fade {{ $locale === 'en' ? 'show active' : '' }}" id="product-lang-{{ $locale }}">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('admin.name') }} ({{ $label }})</label>
                                        <input type="text" wire:model.blur="name.{{ $locale }}" class="form-control @error('name.' . $locale) is-invalid @enderror">
                                        @error('name.' . $locale)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="form-label">{{ __('admin.description') }} ({{ $label }})</label>
                                        <textarea rows="4" wire:model.blur="description.{{ $locale }}" class="form-control @error('description.' . $locale) is-invalid @enderror"></textarea>
                                        @error('description.' . $locale)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <h5 class="mb-0">{{ __('admin.addons') }}</h5>
                        <button type="button" class="btn btn-sm btn-label-primary" wire:click="addAddon">
                            <i class="icon-base ti tabler-plus me-1"></i>
                            {{ __('admin.add_addon') }}
                        </button>
                    </div>
                    <div class="card-body">
                        @forelse ($addons as $index => $addon)
                            <div class="border rounded p-3 mb-3" wire:key="addon-{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">{{ __('admin.addon') }} #{{ $loop->iteration }}</span>
                                    <button type="button" class="btn btn-sm btn-label-danger" wire:click="removeAddon({{ $index }})">
                                        <i class="icon-base ti tabler-trash me-1"></i>
                                        {{ __('admin.remove_addon') }}
                                    </button>
                                </div>
                                <div class="row g-3">
                                    @foreach (['en' => 'EN', 'ru' => 'RU', 'tg' => 'TG'] as $locale => $label)
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('admin.name') }} {{ $label }}</label>
                                            <input type="text" wire:model.blur="addons.{{ $index }}.name.{{ $locale }}" class="form-control @error('addons.' . $index . '.name.' . $locale) is-invalid @enderror">
                                            @error('addons.' . $index . '.name.' . $locale)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach

                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('admin.price') }}</label>
                                        <input type="number" step="0.01" min="0" wire:model.blur="addons.{{ $index }}.price" class="form-control @error('addons.' . $index . '.price') is-invalid @enderror">
                                        @error('addons.' . $index . '.price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="addon-available-{{ $index }}" wire:model="addons.{{ $index }}.is_available">
                                            <label class="form-check-label" for="addon-available-{{ $index }}">
                                                {{ __('admin.available') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">{{ __('admin.no_addons_found') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <h5 class="mb-0">{{ __('admin.variants') }}</h5>
                        <button type="button" class="btn btn-sm btn-label-primary" wire:click="addVariant">
                            <i class="icon-base ti tabler-plus me-1"></i>
                            {{ __('admin.add_variant') }}
                        </button>
                    </div>
                    <div class="card-body">
                        @forelse ($variants as $index => $variant)
                            <div class="border rounded p-3 mb-3" wire:key="variant-{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">{{ __('admin.variant') }} #{{ $loop->iteration }}</span>
                                    <button type="button" class="btn btn-sm btn-label-danger" wire:click="removeVariant({{ $index }})">
                                        <i class="icon-base ti tabler-trash me-1"></i>
                                        {{ __('admin.remove_variant') }}
                                    </button>
                                </div>
                                <div class="row g-3">
                                    @foreach (['en' => 'EN', 'ru' => 'RU', 'tg' => 'TG'] as $locale => $label)
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('admin.name') }} {{ $label }}</label>
                                            <input type="text" wire:model.blur="variants.{{ $index }}.name.{{ $locale }}" class="form-control @error('variants.' . $index . '.name.' . $locale) is-invalid @enderror">
                                            @error('variants.' . $index . '.name.' . $locale)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach

                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('admin.price') }}</label>
                                        <input type="number" step="0.01" min="0" wire:model.blur="variants.{{ $index }}.price" class="form-control @error('variants.' . $index . '.price') is-invalid @enderror">
                                        @error('variants.' . $index . '.price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="variant-available-{{ $index }}" wire:model="variants.{{ $index }}.is_available">
                                            <label class="form-check-label" for="variant-available-{{ $index }}">
                                                {{ __('admin.available') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">{{ __('admin.no_variants_found') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card position-sticky" style="top: 1rem;">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.product_images') }}</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">{{ __('admin.current_images') }}</label>
                        @if ($product->images->isNotEmpty())
                            <div class="row g-3 mb-4">
                                @foreach ($product->images as $image)
                                    @php($marked = in_array((int) $image->id, array_map('intval', $removeImageIds), true))
                                    <div class="col-6">
                                        <div class="position-relative">
                                            <img
                                                src="{{ $image->image_url }}"
                                                class="img-fluid rounded border {{ $marked ? 'opacity-50' : '' }}"
                                                alt="{{ __('admin.product_image') }}"
                                            >
                                            @if ($marked)
                                                <span class="badge bg-label-danger position-absolute top-0 start-0 m-1">
                                                    {{ __('admin.marked_for_removal') }}
                                                </span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm {{ $marked ? 'btn-label-secondary' : 'btn-label-danger' }} w-100 mt-2" wire:click="toggleImageRemoval({{ $image->id }})">
                                            <i class="icon-base ti {{ $marked ? 'tabler-rotate-clockwise' : 'tabler-trash' }} me-1"></i>
                                            {{ $marked ? __('admin.undo') : __('admin.remove_image') }}
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-body-secondary">{{ __('admin.no_product_images') }}</p>
                        @endif

                        <div class="mb-4">
                            <label class="form-label">{{ __('admin.new_images') }}</label>
                            <input type="file" wire:model="newImages" class="form-control @error('newImages') is-invalid @enderror @error('newImages.*') is-invalid @enderror" accept="image/jpeg,image/png" multiple>
                            <small class="text-body-secondary">{{ __('admin.product_images_hint') }}</small>
                            @error('newImages')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('newImages.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div wire:loading wire:target="newImages" class="small text-body-secondary mt-2">
                                {{ __('admin.uploading') }}
                            </div>
                        </div>

                        @if ($newImages)
                            <div class="row g-3 mb-4">
                                @foreach ($newImages as $image)
                                    <div class="col-6">
                                        <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded border" alt="{{ __('admin.product_image') }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="save,newImages">
                            <span wire:loading.remove wire:target="save">
                                <i class="icon-base ti tabler-device-floppy me-1"></i>
                                {{ __('admin.save_product') }}
                            </span>
                            <span wire:loading wire:target="save">
                                {{ __('admin.saving') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
