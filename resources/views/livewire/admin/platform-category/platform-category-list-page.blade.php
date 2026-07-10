<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.platform_categories') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.platform_categories_description') }}</p>
                </div>
                <a href="{{ route('admin.platform-categories.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>
                    {{ __('admin.add_category') }}
                </a>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.search_categories_placeholder') }}"
                            wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="platformFilter">
                        <option value="">{{ __('admin.all_platforms') }}</option>
                        @foreach ($platforms as $platform)
                            <option value="{{ $platform->id }}">{{ $platform->getTranslation('name', 'en') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-label-primary fs-6">{{ __('admin.total') }}: {{ $categories->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>{{ __('admin.category') }}</th>
                        <th>{{ __('admin.keyword') }}</th>
                        <th>{{ __('admin.platforms') }}</th>
                        <th width="140">{{ __('admin.status') }}</th>
                        <th width="140">{{ __('admin.is_default') }}</th>
                        <th width="180">{{ __('admin.created') }}</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><span class="fw-semibold">#{{ $category->id }}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    @foreach (['en' => '🇺🇸', 'ru' => '🇷🇺', 'tg' => '🇹🇯'] as $locale => $flag)
                                        <span
                                            class="{{ $loop->first ? 'fw-semibold text-dark' : 'text-body-secondary' }} {{ $loop->first ? '' : 'small' }}">
                                            {{ $flag }} {{ $category->getTranslation('name', $locale) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td><code>{{ $category->keyword }}</code></td>
                            <td>
                                <span
                                    class="badge bg-label-primary">{{ $category->platform?->getTranslation('name', 'en') ?? '—' }}</span>
                                @if ($category->platform?->code)
                                    <small class="text-body-secondary d-block">{{ $category->platform->code }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge bg-label-success">{{ __('admin.active') }}</span>
                                @else
                                    <span class="badge bg-label-danger">{{ __('admin.inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($category->is_default)
                                    <span class="badge bg-label-success">{{ __('admin.yes') }}</span>
                                @else
                                    <span class="badge bg-label-danger">{{ __('admin.no') }}</span>
                                @endif
                            </td>

                            <td>
                                <div>{{ $category->created_at->format('d M Y') }}</div>
                                <small class="text-body-secondary">{{ $category->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.platform-categories.edit', $category) }}"
                                                class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>{{ __('admin.edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item"
                                                wire:click="toggleStatus({{ $category->id }})">
                                                <i class="icon-base ti tabler-refresh me-2"></i>
                                                {{ $category->is_active ? __('admin.deactivate') : __('admin.activate') }}
                                            </button>
                                        </li>
                                        @if (!$category->is_default)
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger"
                                                    wire:click="delete({{ $category->id }})">
                                                    <i class="icon-base ti tabler-trash me-2"></i>{{ __('admin.delete') }}
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-5">
                                    <i class="icon-base ti tabler-category" style="font-size: 60px"></i>
                                    <h5 class="mt-3">{{ __('admin.no_platform_categories_found') }}</h5>
                                    <p class="text-body-secondary mb-4">{{ __('admin.no_platform_categories_found_hint') }}
                                    </p>
                                    <a href="{{ route('admin.platform-categories.create') }}"
                                        class="btn btn-primary">{{ __('admin.add_category') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="card-footer">{{ $categories->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
