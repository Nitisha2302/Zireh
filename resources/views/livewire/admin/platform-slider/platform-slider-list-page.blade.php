<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">{{ __('admin.platform_sliders') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.platform_sliders_description') }}</p>
                </div>
                <a href="{{ route('admin.platform-sliders.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>{{ __('admin.add_slider') }}
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="110">{{ __('admin.image') }}</th>
                        <th>{{ __('admin.heading') }}</th>
                        <th>{{ __('admin.platforms') }}</th>
                        <th width="120">{{ __('admin.link') }}</th>
                        <th width="80"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sliders as $slider)
                        <tr>
                            <td><img src="{{ app(\App\Services\FileManager::class)->url($slider->image) }}" class="rounded border object-fit-cover" style="width: 72px; height: 50px;" alt="{{ $slider->heading }}"></td>
                            <td class="fw-semibold">{{ $slider->heading }}</td>
                            <td>
                                @forelse ($slider->platforms as $platform)
                                    <span class="badge bg-label-primary me-1 mb-1">{{ $platform->getTranslation('name', app()->getLocale()) ?: $platform->getTranslation('name', 'en') }}</span>
                                @empty
                                    <span class="text-body-secondary">{{ __('admin.no_platforms_assigned') }}</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($slider->link)
                                    <a href="{{ $slider->link }}" target="_blank" rel="noopener" class="btn btn-sm btn-label-secondary"><i class="icon-base ti tabler-external-link"></i></a>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a href="{{ route('admin.platform-sliders.edit', $slider) }}" class="dropdown-item"><i class="icon-base ti tabler-pencil me-2"></i>{{ __('admin.edit') }}</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button type="button" wire:click="delete({{ $slider->id }})" class="dropdown-item text-danger"><i class="icon-base ti tabler-trash me-2"></i>{{ __('admin.delete') }}</button></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-5">
                                    <i class="icon-base ti tabler-photo-off" style="font-size: 60px"></i>
                                    <h5 class="mt-3">{{ __('admin.no_platform_sliders_found') }}</h5>
                                    <a href="{{ route('admin.platform-sliders.create') }}" class="btn btn-primary mt-2">{{ __('admin.add_slider') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sliders->hasPages())
            <div class="card-footer">{{ $sliders->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
