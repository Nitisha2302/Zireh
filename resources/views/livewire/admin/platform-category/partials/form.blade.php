<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.category_details') }}</h5>
                <small class="text-body-secondary">{{ __('admin.category_details_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="platformId">{{ __('admin.platforms') }}</label>
                    <select id="platformId" wire:model.blur="platformId" class="form-select @error('platformId') is-invalid @enderror">
                        <option value="">{{ __('admin.select_platform') }}</option>
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
                    <label class="form-label" for="keyword">{{ __('admin.keyword') }}</label>
                    <input id="keyword" type="text" wire:model.blur="keyword" class="form-control @error('keyword') is-invalid @enderror" placeholder="women-fashion">
                    <small class="text-body-secondary">{{ __('admin.category_keyword_hint') }}</small>
                    @error('keyword') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">{{ __('admin.active') }}</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ isset($platformCategory) ? __('admin.update_category') : __('admin.save_category') }}
                </button>
                <a href="{{ route('admin.platform-categories.index') }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
