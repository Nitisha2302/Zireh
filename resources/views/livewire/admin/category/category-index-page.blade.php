<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        {{-- Header --}}
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">
                        Categories
                    </h4>
                    <p class="mb-0 text-body-secondary">
                        Manage categories available in restaurants
                    </p>
                </div>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>
                    Add Category
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="icon-base ti tabler-search"></i>
                        </span>

                        <input type="text" class="form-control" placeholder="Search cuisines..."
                            wire:model.live.debounce.500ms="search">
                    </div>
                </div>

                <div class="col-md-7 text-md-end">
                    <span class="badge bg-label-primary fs-6">
                        Total:
                        {{ $categories->total() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th width="80">Image</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Cuisine</th>
                        <th width="140">Status</th>
                        <th width="180">Created</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <span class="fw-semibold">
                                    #{{ $category->id }}
                                </span>
                            </td>
                            <td>
                                @if ($category->image)
                                    <img src="{{ $category->image_url }}"
                                        class="img-fluid rounded border" alt="{{ $category->name }}">
                                @else
                                    <div class="d-flex justify-content-center align-items-center bg-light rounded"
                                        style="width: 60px; height: 60px;">
                                        <i class="icon-base ti tabler-image-off" style="font-size: 24px;"></i>
                                    </div>
                                @endif
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark">
                                        🇺🇸
                                        {{ $category->getTranslation('name', 'en') }}
                                    </span>

                                    <small class="text-body-secondary">
                                        🇷🇺
                                        {{ $category->getTranslation('name', 'ru') }}
                                    </small>

                                    <small class="text-body-secondary">
                                        🇹🇯
                                        {{ $category->getTranslation('name', 'tg') }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark">
                                        🇺🇸
                                        {{ Str::limit($category->getTranslation('description', 'en'), 50) ?? '—' }}
                                    </span>

                                    <small class="text-body-secondary">
                                        🇷🇺
                                        {{ Str::limit($category->getTranslation('description', 'ru'), 50) ?? '—' }}
                                    </small>

                                    <small class="text-body-secondary">
                                        🇹🇯
                                        {{ Str::limit($category->getTranslation('description', 'tg'), 50) ?? '—' }}
                                    </small>
                                </div>

                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark">
                                        🇺🇸
                                        {{ $category?->cuisine?->getTranslation('name', 'en') ?? '—' }} 
                                    </span>
                                    <small class="text-body-secondary">
                                        🇷🇺
                                        {{ $category?->cuisine?->getTranslation('name', 'ru') ?? '—' }} 
                                    </small>
                                    <small class="text-body-secondary">
                                        🇹🇯
                                        {{ $category?->cuisine?->getTranslation('name', 'tg') ?? '—' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                @if ($category->status)
                                    <span class="badge bg-label-success">
                                        Active
                                    </span>
                                @else
                                    <span class="badge bg-label-danger">
                                        Inactive
                                    </span>
                                @endif

                            </td>

                            <td>
                                <div>
                                    {{ $category->created_at->format('d M Y') }}
                                </div>
                                <small class="text-body-secondary">
                                    {{ $category->created_at->diffForHumans() }}
                                </small>
                            </td>

                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.categories.edit', $category) }}"
                                                class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>
                                                Edit
                                            </a>
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item"
                                                wire:click="toggleStatus({{ $category->id }})">

                                                <i class="icon-base ti tabler-refresh me-2"></i>

                                                {{ $category->status ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </li>

                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item text-danger"
                                                wire:click="delete({{ $category->id }})">
                                                <i class="icon-base ti tabler-trash me-2"></i>
                                                Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="icon-base ti tabler-chef-hat" style="font-size:70px"></i>
                                    </div>
                                    <h5 class="mb-2">
                                        No categories found
                                    </h5>
                                    <p class="text-body-secondary mb-4">
                                        Start by creating your first category.
                                    </p>
                                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                                        <i class="icon-base ti tabler-plus me-1"></i>
                                        Add Category
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        @if ($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links('livewire::bootstrap') }}
            </div>
        @endif
    </div>
</div>
