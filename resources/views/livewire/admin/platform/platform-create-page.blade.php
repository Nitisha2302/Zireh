<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="save">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">Platform details</h5><small class="text-body-secondary">Enter the platform name
                            and logo for each language.</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label" for="code">Platform code</label>
                            <input id="code" type="text" wire:model.blur="code"
                                class="form-control @error('code') is-invalid @enderror" placeholder="taobao">
                            <small class="text-body-secondary">Optional API identifier (e.g. taobao, 1688). Lowercase
                                letters and numbers only.</small>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @include('livewire.admin.platform.partials.translation-fields', [
                            'editing' => false,
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Publish</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox"
                                id="is_available" wire:model="is_available"><label class="form-check-label"
                                for="is_available">Active</label></div>
                        <button type="submit" class="btn btn-primary w-100"><i
                                class="icon-base ti tabler-device-floppy"></i> Save Platform</button>
                        <a href="{{ route('admin.platforms.index') }}"
                            class="btn btn-label-secondary w-100 mt-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@include('livewire.admin.platform.partials.tab-script')
