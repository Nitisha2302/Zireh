<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.lessons') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.lessons_description') }}</p>
                </div>
                <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>
                    {{ __('admin.add_lesson') }}
                </a>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="input-group">
                <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                <input type="text" class="form-control" placeholder="{{ __('admin.search_lessons') }}" wire:model.live.debounce.500ms="search">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="110">{{ __('admin.image') }}</th>
                        <th>{{ __('admin.title') }}</th>
                        <th>{{ __('admin.description') }}</th>
                        <th width="140">{{ __('admin.status') }}</th>
                        <th width="80"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lessons as $lesson)
                        <tr>
                            <td>
                                <img
                                    src="{{ app(\App\Services\FileManager::class)->url($lesson->image) }}"
                                    class="rounded border object-fit-cover"
                                    style="width: 72px; height: 50px;"
                                    alt="{{ $lesson->getTranslation('title', 'en') }}"
                                >
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @foreach (['en' => '🇺🇸', 'ru' => '🇷🇺', 'tg' => '🇹🇯'] as $locale => $flag)
                                        <span class="{{ $loop->first ? 'fw-semibold text-dark' : 'text-body-secondary small' }}">
                                            {{ $flag }} {{ $lesson->getTranslation('title', $locale) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="text-body-secondary">{{ \Illuminate\Support\Str::limit($lesson->getTranslation('description', 'en'), 80) }}</span>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        @checked($lesson->is_active)
                                        wire:click="toggleStatus({{ $lesson->id }})"
                                    >
                                    <label class="form-check-label">
                                        {{ $lesson->is_active ? __('admin.active') : __('admin.inactive') }}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>{{ __('admin.edit') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" wire:click="delete({{ $lesson->id }})" class="dropdown-item text-danger">
                                                <i class="icon-base ti tabler-trash me-2"></i>{{ __('admin.delete') }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-5">
                                    <i class="icon-base ti tabler-book-off" style="font-size: 60px"></i>
                                    <h5 class="mt-3">{{ __('admin.no_lessons_found') }}</h5>
                                    <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary mt-2">{{ __('admin.add_lesson') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lessons->hasPages())
            <div class="card-footer">{{ $lessons->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
