<div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">

        {{-- Header --}}
        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h4 class="mb-1">
                        Plateform
                    </h4>

                    <p class="mb-0 text-body-secondary">
                        Manage Plateform available 
                    </p>

                </div>

                <a href="{{ route('admin.platforms.create') }}" class="btn btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i>
                    Add Plateform
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
                        {{ $platforms->total() }}

                    </span>

                </div>

            </div>

        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">
                            ID
                        </th>
                        <th width="90">
                            Image
                        </th>
                        <th>
                            Plateform
                        </th>
                        
                        <th width="140">
                            Status
                        </th>
                        <th width="180">
                            Created
                        </th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($platforms as $platform)
                        <tr>
                            <td>
                                <span class="fw-semibold">
                                    #{{ $platform->id }}
                                </span>
                            </td>
                            <td>
                                @if ($plateform->image)
                                    <img src="{{ $platform->image_url }}" class="rounded border object-fit-cover"
                                        style="width: 56px; height: 56px;" alt="Cuisine image">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded border"
                                        style="width: 56px; height: 56px;">
                                        <i class="icon-base ti tabler-photo-off"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark">
                                        🇺🇸
                                        {{ $platform->getTranslation('name', 'en') }}
                                    </span>
                                    <small class="text-body-secondary">
                                        🇷🇺
                                        {{ $platform->getTranslation('name', 'ru') }}
                                    </small>
                                    <small class="text-body-secondary">
                                        🇹🇯
                                        {{ $platform->getTranslation('name', 'tg') }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                @if ($platform->status)
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
                                    {{ $platform->created_at->format('d M Y') }}
                                </div>
                                <small class="text-body-secondary">
                                    {{ $platform->created_at->diffForHumans() }}
                                </small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.platforms.edit', $platform) }}"
                                                class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>
                                                Edit
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item"
                                                wire:click="toggleStatus({{ $platform->id }})">
                                                <i class="icon-base ti tabler-refresh me-2"></i>
                                                {{ $platform->is_available ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger"
                                                wire:click="delete({{ $platform->id }})">
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
                            <td colspan="6">
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="icon-base ti tabler-chef-hat" style="font-size:70px"></i>
                                    </div>
                                    <h5 class="mb-2">
                                        No Platform found
                                    </h5>
                                    <p class="text-body-secondary mb-4">
                                        Start by creating your first Platform.
                                    </p>
                                    <a href="{{ route('admin.platforms.create') }}" class="btn btn-primary">
                                        <i class="icon-base ti tabler-plus me-1"></i>
                                        Add Platform
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($platforms->hasPages())
            <div class="card-footer">
                {{ $platforms->links('livewire::bootstrap') }}
            </div>
        @endif
    </div>
</div>
