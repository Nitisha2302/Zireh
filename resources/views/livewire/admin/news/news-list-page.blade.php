<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">News</h4>
                    <p class="mb-0 text-body-secondary">Manage news articles shown in the mobile app.</p>
                </div>
                <a href="{{ route('admin.news.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>
                    Add News
                </a>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="input-group">
                <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                <input type="text" class="form-control" placeholder="Search news..." wire:model.live.debounce.500ms="search">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="110">Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th width="140">Status</th>
                        <th width="80"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($newsItems as $newsItem)
                        <tr>
                            <td>
                                <img
                                    src="{{ app(\App\Services\FileManager::class)->url($newsItem->image) }}"
                                    class="rounded border object-fit-cover"
                                    style="width: 72px; height: 50px;"
                                    alt="{{ $newsItem->getTranslation('title', 'en') }}"
                                >
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @foreach (['en' => '🇺🇸', 'ru' => '🇷🇺', 'tg' => '🇹🇯'] as $locale => $flag)
                                        <span class="{{ $loop->first ? 'fw-semibold text-dark' : 'text-body-secondary small' }}">
                                            {{ $flag }} {{ $newsItem->getTranslation('title', $locale) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="text-body-secondary">{{ \Illuminate\Support\Str::limit($newsItem->getTranslation('description', 'en'), 80) }}</span>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        @checked($newsItem->is_active)
                                        wire:click="toggleStatus({{ $newsItem->id }})"
                                    >
                                    <label class="form-check-label">
                                        {{ $newsItem->is_active ? 'Active' : 'Inactive' }}
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
                                            <a href="{{ route('admin.news.edit', $newsItem) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" wire:click="delete({{ $newsItem->id }})" class="dropdown-item text-danger">
                                                <i class="icon-base ti tabler-trash me-2"></i>Delete
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
                                    <i class="icon-base ti tabler-news-off" style="font-size: 60px"></i>
                                    <h5 class="mt-3">No news found</h5>
                                    <a href="{{ route('admin.news.create') }}" class="btn btn-primary mt-2">Add News</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($newsItems->hasPages())
            <div class="card-footer">{{ $newsItems->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
