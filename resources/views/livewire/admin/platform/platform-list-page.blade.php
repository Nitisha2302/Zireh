<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Platforms</h4>
                    <p class="mb-0 text-body-secondary">Manage available platforms.</p>
                </div><a href="{{ route('admin.platforms.create') }}" class="btn btn-primary"><i
                        class="icon-base ti tabler-plus me-1"></i> Add Platform</a>
            </div>
        </div>
        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group"><span class="input-group-text"><i
                                class="icon-base ti tabler-search"></i></span><input type="text" class="form-control"
                            placeholder="Search platforms..." wire:model.live.debounce.500ms="search"></div>
                </div>
                <div class="col-md-7 text-md-end"><span class="badge bg-label-primary fs-6">Total:
                        {{ $platforms->total() }}</span></div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Platform translations and logos</th>
                        <th width="140">Code</th>
                        <th width="140">Commision</th>
                        <th width="140">Status</th>
                        <th width="180">Created</th>
                        <th width="100"></th>
                    </tr>
                    {{-- @include('livewire.admin.partials._table-header') --}}
                </thead>
                <tbody>
                    @forelse($platforms as $platform)
                        <tr>
                            <td><span class="fw-semibold">#{{ $platform->id }}</span></td>
                            <td>
                                @foreach (['en' => 'English', 'ru' => 'Russian', 'tg' => 'Tajik'] as $locale => $label)
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
                            <td><code>{{ $platform->commission }}%</code></td>
                            <td>
                                @if ($platform->is_available)
                                <span class="badge bg-label-success">Active</span>@else<span
                                        class="badge bg-label-danger">Inactive</span>
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
                                        <li><a href="{{ route('admin.platforms.edit', $platform) }}"
                                                class="dropdown-item"><i
                                                    class="icon-base ti tabler-pencil me-2"></i>Edit</a></li>
                                        <li><button type="button" class="dropdown-item"
                                                wire:click="toggleStatus({{ $platform->id }})"><i
                                                    class="icon-base ti tabler-refresh me-2"></i>{{ $platform->is_available ? 'Deactivate' : 'Activate' }}</button>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        {{-- <li><button type="button" class="dropdown-item text-danger"
                                                wire:click="delete({{ $platform->id }})"><i
                                                    class="icon-base ti tabler-trash me-2"></i>Delete</button></li> --}}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-5">
                                    <div class="mb-3"><i class="icon-base ti tabler-app-window"
                                            style="font-size:70px"></i></div>
                                    <h5 class="mb-2">No platforms found</h5>
                                    <p class="text-body-secondary mb-4">Start by creating your first platform.</p><a
                                        href="{{ route('admin.platforms.create') }}" class="btn btn-primary"><i
                                            class="icon-base ti tabler-plus me-1"></i>Add Platform</a>
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
