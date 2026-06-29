<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Commission Slabs</h4>
                    <p class="mb-0 text-body-secondary">
                        Manage commission slabs for <strong>{{ $platform->getTranslation('name', 'en') }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.platforms.commission-slabs.create', $platform) }}" class="btn btn-primary">
                        <i class="icon-base ti tabler-plus me-1"></i>Add Slab
                    </a>
                    <a href="{{ route('admin.platforms.edit', $platform) }}" class="btn btn-label-secondary">
                        Back to Platform
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Minimum Amount</th>
                        <th>Maximum Amount</th>
                        <th width="160">Commission (%)</th>
                        <th width="140">Status</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($slabs as $slab)
                        <tr>
                            <td><span class="fw-semibold">#{{ $slab->id }}</span></td>
                            <td>{{ number_format($slab->min_amount, 2) }}</td>
                            <td>
                                @if ($slab->isUnlimited())
                                    <span class="badge bg-label-info">Unlimited</span>
                                @else
                                    {{ number_format($slab->max_amount, 2) }}
                                @endif
                            </td>
                            <td><code>{{ number_format($slab->commission_percentage, 2) }}%</code></td>
                            <td>
                                @if ($slab->is_active)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.platforms.commission-slabs.edit', [$platform, $slab]) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $slab->id }})">
                                                <i class="icon-base ti tabler-refresh me-2"></i>
                                                {{ $slab->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $slab->id }})">
                                                <i class="icon-base ti tabler-trash me-2"></i>Delete
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
                                    <i class="icon-base ti tabler-percentage" style="font-size: 60px"></i>
                                    <h5 class="mt-3">No commission slabs found</h5>
                                    <p class="text-body-secondary mb-4">Add slabs to define commission by transaction amount.</p>
                                    <a href="{{ route('admin.platforms.commission-slabs.create', $platform) }}" class="btn btn-primary">Add Slab</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
