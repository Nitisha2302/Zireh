<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-1">Slider details</h5><small class="text-body-secondary">Choose one or more platforms that should show this slider.</small></div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="heading">Heading</label>
                    <input id="heading" type="text" wire:model.blur="heading" class="form-control @error('heading') is-invalid @enderror" placeholder="Summer offers">
                    @error('heading') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="link">Link <span class="text-body-secondary">(optional)</span></label>
                    <input id="link" type="url" wire:model.blur="link" class="form-control @error('link') is-invalid @enderror" placeholder="https://example.com/offers">
                    @error('link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <label class="form-label">Platforms</label>
                <div class="border rounded p-3" style="max-height: 320px; overflow-y: auto;">
                    @forelse ($platforms as $platform)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="{{ $platform->id }}" id="platform-{{ $platform->id }}" wire:model="platformIds">
                            <label class="form-check-label" for="platform-{{ $platform->id }}">
                                {{ $platform->getTranslation('name', 'en') }}
                                <small class="text-body-secondary">— {{ $platform->getTranslation('name', 'ru') }}</small>
                            </label>
                        </div>
                    @empty
                        <span class="text-body-secondary">No platforms are available yet.</span>
                    @endforelse
                </div>
                @error('platformIds') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h5 class="mb-0">Slider image</h5></div><div class="card-body">
            <input type="file" accept="image/*" wire:model="image" class="form-control @error('image') is-invalid @enderror">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div wire:loading wire:target="image" class="small text-muted mt-2">Uploading...</div>
            <div class="mt-3">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded border" alt="Slider preview">
                @elseif (isset($platformSlider))
                    <img src="{{ app(\App\Services\FileManager::class)->url($platformSlider->image) }}" class="img-fluid rounded border" alt="Slider image">
                @endif
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-4"><i class="icon-base ti tabler-device-floppy"></i> {{ isset($platformSlider) ? 'Update Slider' : 'Save Slider' }}</button>
            <a href="{{ route('admin.platform-sliders.index') }}" class="btn btn-label-secondary w-100 mt-2">Cancel</a>
        </div></div>
    </div>
</div>
