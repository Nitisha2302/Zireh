<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.news_details') }}</h5>
                <small class="text-body-secondary">{{ __('admin.news_details_hint') }}</small>
            </div>
            <div class="card-body">
                @include('livewire.admin.partials.translation-content-fields')
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.news_image') }}</h5></div>
            <div class="card-body">
                <input type="file" accept="image/*" wire:model="image" class="form-control @error('image') is-invalid @enderror">
                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div wire:loading wire:target="image" class="small text-muted mt-2">{{ __('admin.uploading') }}</div>
                <div class="mt-3">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded border" alt="{{ __('admin.news_image') }}">
                    @elseif (isset($record))
                        <img src="{{ app(\App\Services\FileManager::class)->url($record->image) }}" class="img-fluid rounded border" alt="{{ __('admin.news_image') }}">
                    @endif
                </div>

                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">{{ __('admin.active') }}</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-4">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ isset($record) ? __('admin.update_news') : __('admin.save_news') }}
                </button>
                <a href="{{ route('admin.news.index') }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
