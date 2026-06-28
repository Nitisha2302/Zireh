<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Category details</h5>
                <small class="text-body-secondary">Assign a category to a platform with a search keyword in all supported languages.</small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="platformId">Platform</label>
                    <select id="platformId" wire:model.blur="platformId" class="form-select @error('platformId') is-invalid @enderror">
                        <option value="">Select platform</option>
                        @foreach ($platforms as $platform)
                            <option value="{{ $platform->id }}">
                                {{ $platform->getTranslation('name', 'en') }}
                                @if ($platform->code)
                                    ({{ $platform->code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('platformId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @include('livewire.admin.platform-category.partials.translation-fields')

                <div class="mt-4">
                    <label class="form-label" for="keyword">Keyword</label>
                    <input id="keyword" type="text" wire:model.blur="keyword" class="form-control @error('keyword') is-invalid @enderror" placeholder="women-fashion">
                    <small class="text-body-secondary">Used as the category identifier in API responses. Lowercase letters, numbers, hyphens, and underscores only.</small>
                    @error('keyword') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    {{ isset($platformCategory) ? 'Update Category' : 'Save Category' }}
                </button>
                <a href="{{ route('admin.platform-categories.index') }}" class="btn btn-label-secondary w-100 mt-2">Cancel</a>
            </div>
        </div>
    </div>
</div>
