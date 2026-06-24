<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">{{ __('admin.menus') }}</h5>
                <p class="mb-0 text-body-secondary">{{ __('admin.restaurant_menus_description') }}</p>
            </div>
            <span class="badge bg-label-primary">{{ __('admin.total') }}: {{ $menus->total() }}</span>
        </div>
    </div>
    <div class="card-body border-top border-bottom">
        <div class="input-group">
            <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
            <input type="text" class="form-control" placeholder="{{ __('admin.search_menus_placeholder') }}"
                wire:model.live.debounce.500ms="search">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('admin.menu') }}</th>
                    <th>{{ __('admin.availability') }}</th>
                    <th>{{ __('admin.assigned_products') }}</th>
                    <th>{{ __('admin.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($menus as $menu)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($menu->image_path)
                                    <img src="{{ app(\App\Services\FileManager::class)->url($menu->image_path) }}"
                                        alt="{{ __('admin.menu_image') }}" class="rounded border object-fit-cover"
                                        style="width: 52px; height: 52px;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded border"
                                        style="width: 52px; height: 52px;">
                                        <i class="icon-base ti tabler-tools-kitchen-2"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">
                                        {{ $menu->getTranslation('name', app()->getLocale(), false) ?: $menu->getTranslation('name', 'en', false) ?: __('admin.not_available') }}
                                    </div>
                                    <small class="text-body-secondary">
                                        {{ str($menu->getTranslation('description', app()->getLocale(), false) ?: $menu->getTranslation('description', 'en', false) ?: __('admin.not_available'))->limit(90) }}
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @forelse ($menu->availabilitySchedules->take(4) as $schedule)
                                <small class="d-block text-body-secondary">
                                    {{ str($schedule->day_name)->title() }}:
                                    {{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}
                                </small>
                            @empty
                                <span class="text-body-secondary">{{ __('admin.no_schedule_found') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge bg-label-secondary mb-2">{{ $menu->products_count }}</span>
                            @forelse ($menu->products->take(3) as $product)
                                <small class="d-block text-body-secondary">
                                    {{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->getTranslation('name', 'en', false) }}
                                </small>
                            @empty
                                <small class="d-block text-body-secondary">{{ __('admin.no_assigned_products') }}</small>
                            @endforelse
                        </td>
                        <td><span class="badge bg-label-primary">{{ str($menu->status)->title() }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-body-secondary py-5">{{ __('admin.no_menus_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($menus->hasPages())
        <div class="card-footer">
            {{ $menus->links('livewire::bootstrap') }}
        </div>
    @endif
</div>
