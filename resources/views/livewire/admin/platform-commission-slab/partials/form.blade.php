<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Slab details</h5>
                <small class="text-body-secondary">Define the transaction amount range and commission percentage.</small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="minAmount">Minimum amount</label>
                    <input id="minAmount" type="number" step="0.01" min="0" wire:model.blur="minAmount"
                        class="form-control @error('minAmount') is-invalid @enderror" placeholder="1">
                    @error('minAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="isUnlimited" wire:model.live="isUnlimited">
                    <label class="form-check-label" for="isUnlimited">Unlimited maximum amount</label>
                </div>

                @unless ($isUnlimited)
                    <div class="mb-4">
                        <label class="form-label" for="maxAmount">Maximum amount</label>
                        <input id="maxAmount" type="number" step="0.01" min="0" wire:model.blur="maxAmount"
                            class="form-control @error('maxAmount') is-invalid @enderror" placeholder="1000">
                        @error('maxAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endunless

                <div class="mb-0">
                    <label class="form-label" for="commissionPercentage">Commission percentage</label>
                    <input id="commissionPercentage" type="number" step="0.01" min="0" max="100" wire:model.blur="commissionPercentage"
                        class="form-control @error('commissionPercentage') is-invalid @enderror" placeholder="10">
                    @error('commissionPercentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Publish</h5></div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">Active</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ isset($slab) ? 'Update Slab' : 'Save Slab' }}
                </button>
                <a href="{{ route('admin.platforms.commission-slabs.index', $platform) }}" class="btn btn-label-secondary w-100 mt-2">Cancel</a>
            </div>
        </div>
    </div>
</div>
