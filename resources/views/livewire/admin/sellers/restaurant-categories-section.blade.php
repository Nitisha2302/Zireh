<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">{{ __('admin.categories') }}</h5>
                <p class="mb-0 text-body-secondary">{{ __('admin.restaurant_categories_description') }}</p>
            </div>
            <span class="badge bg-label-primary">{{ __('admin.total') }}: {{ $categories->total() }}</span>
        </div>
    </div>
    <div class="card-body border-top border-bottom">
        <div class="input-group">
            <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
            <input type="text" class="form-control" placeholder="{{ __('admin.search_categories_placeholder') }}"
                wire:model.live.debounce.500ms="search">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('admin.category') }}</th>
                    <th>{{ __('admin.cuisine') }}</th>
                    <th>{{ __('admin.description') }}</th>
                    <th>{{ __('admin.products') }}</th>
                    <th>{{ __('admin.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($category->image)
                                    <img src="{{ app(\App\Services\FileManager::class)->url($category->image) }}"
                                        alt="{{ __('admin.category') }}" class="rounded border object-fit-cover"
                                        style="width: 48px; height: 48px;">
                                @endif
                                <div class="fw-semibold">
                                    {{ $category->getTranslation('name', app()->getLocale(), false) ?: $category->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $category->cuisine?->getTranslation('name', app()->getLocale(), false) ?: $category->cuisine?->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                        </td>
                        <td>
                            <span class="text-body-secondary">
                                {{ str($category->getTranslation('description', app()->getLocale(), false) ?: $category->getTranslation('description', 'en', false) ?: __('admin.not_available'))->limit(90) }}
                            </span>
                        </td>
                        <td><span class="badge bg-label-secondary">{{ $category->products_count }}</span></td>
                        <td>
                            <span class="badge {{ $category->status ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ $category->status ? __('admin.active') : __('admin.inactive') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-body-secondary py-5">{{ __('admin.no_categories_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($categories->hasPages())
        <div class="card-footer">
            {{ $categories->links('livewire::bootstrap') }}
        </div>
    @endif
</div>
