<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.platforms') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.platforms_description') }}</p>
                </div><a href="{{ route('admin.platforms.create') }}" class="btn btn-primary"><i
                        class="icon-base ti tabler-plus me-1"></i> {{ __('admin.add_platform') }}</a>
            </div>
        </div>
        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group"><span class="input-group-text"><i
                                class="icon-base ti tabler-search"></i></span><input type="text" class="form-control"
                            placeholder="{{ __('admin.search_platforms_placeholder') }}" wire:model.live.debounce.500ms="search"></div>
                </div>
                <div class="col-md-7 text-md-end"><span class="badge bg-label-primary fs-6">{{ __('admin.total') }}:
                        {{ $platforms->total() }}</span></div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>{{ __('admin.platform_translations_logos') }}</th>
                        <th width="140">{{ __('admin.code') }}</th>
                        <th width="140">{{ __('admin.commission_slabs') }}</th>
                        <th width="140">{{ __('admin.status') }}</th>
                        <th width="180">{{ __('admin.created') }}</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($platforms as $platform)
                        <tr>
                            <td><span class="fw-semibold">#{{ $platform->id }}</span></td>
                            <td>
                                @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
                                    @php($logo = $platform->getTranslation('logo', $locale, false))
                                    <div class="d-flex align-items-center gap-2 {{ $loop->first ? '' : 'mt-2' }}">
                                        @if ($logo)
                                            <img src="{{ app(\App\Services\FileManager::class)->url($logo) }}"
                                                class="rounded border object-fit-cover"
                                            style="width: 38px; height: 38px;" alt="{{ $label }} logo">@else
                                            <div class="d-flex align-items-center justify-content-center bg-light rounded border"
                                                style="width: 38px; height: 38px;"><i
                                                    class="icon-base ti tabler-photo-off"></i></div>
                                        @endif
                                        <div><small class="text-body-secondary">{{ $label }}</small>
                                            <div class="fw-semibold">{{ $platform->getTranslation('name', $locale) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td><code>{{ $platform->code }}</code></td>
                            <td>
                                <a href="{{ route('admin.platforms.commission-slabs.index', $platform) }}" class="badge bg-label-primary text-decoration-none">
                                    {{ __('admin.slab_count', ['count' => $platform->commission_slabs_count]) }}
                                </a>
                            </td>
                            <td>
                                @if ($platform->is_available)
                                <span class="badge bg-label-success">{{ __('admin.active') }}</span>@else<span
                                        class="badge bg-label-danger">{{ __('admin.inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $platform->created_at->format('d M Y') }}</div><small
                                    class="text-body-secondary">{{ $platform->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="dropdown"><button class="btn btn-sm btn-icon" data-bs-toggle="dropdown"><i
                                            class="icon-base ti tabler-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a href="{{ route('admin.platforms.commission-slabs.index', $platform) }}"
                                                class="dropdown-item"><i
                                                    class="icon-base ti tabler-percentage me-2"></i>{{ __('admin.commission_slabs') }}</a></li>
                                        <li><a href="{{ route('admin.platforms.edit', $platform) }}"
                                                class="dropdown-item"><i
                                                    class="icon-base ti tabler-pencil me-2"></i>{{ __('admin.edit') }}</a></li>
                                        <li><button type="button" class="dropdown-item"
                                                wire:click="toggleStatus({{ $platform->id }})"><i
                                                    class="icon-base ti tabler-refresh me-2"></i>{{ $platform->is_available ? __('admin.deactivate') : __('admin.activate') }}</button>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="text-center py-5">
                                    <div class="mb-3"><i class="icon-base ti tabler-app-window"
                                            style="font-size:70px"></i></div>
                                    <h5 class="mb-2">{{ __('admin.no_platforms_found') }}</h5>
                                    <p class="text-body-secondary mb-4">{{ __('admin.no_platforms_found_hint') }}</p><a
                                        href="{{ route('admin.platforms.create') }}" class="btn btn-primary"><i
                                            class="icon-base ti tabler-plus me-1"></i>{{ __('admin.add_platform') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($platforms->hasPages())
            <div class="card-footer">{{ $platforms->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
