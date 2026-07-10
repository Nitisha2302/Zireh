<ul class="nav nav-tabs mb-4" role="tablist">
    @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
        <li class="nav-item" role="presentation"><button id="btn-{{ $locale }}" type="button"
                class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                data-bs-target="#tab-{{ $locale }}">{{ $label }}</button></li>
    @endforeach
</ul>
<div class="tab-content">
    @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $locale }}" role="tabpanel">
            <div class="mb-4">
                <label class="form-label" for="name-{{ $locale }}">{{ __('admin.platform_name_in', ['language' => $label]) }}</label>
                <input id="name-{{ $locale }}" type="text"
                    class="form-control @error("name.$locale") is-invalid @enderror"
                    wire:model.blur="name.{{ $locale }}" placeholder="{{ __('admin.platform_name_in', ['language' => $label]) }}">
                @error("name.$locale")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="logo-{{ $locale }}">{{ __('admin.logo_in', ['language' => $label]) }}</label>
                <input id="logo-{{ $locale }}" type="file" accept="image/*"
                    wire:model="logos.{{ $locale }}"
                    class="form-control @error("logos.$locale") is-invalid @enderror">
                @error("logos.$locale")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div wire:loading wire:target="logos.{{ $locale }}" class="small text-muted mt-2">{{ __('admin.uploading') }}
                </div>
                <div class="mt-3">
                    @if ($logos[$locale])
                        <img src="{{ $logos[$locale]->temporaryUrl() }}" class="rounded border object-fit-cover"
                            style="width: 96px; height: 96px;" alt="{{ $label }} logo preview">
                    @elseif ($editing && ($logo = $platform->getTranslation('logo', $locale, false)))
                        <img src="{{ app(\App\Services\FileManager::class)->url($logo) }}"
                            class="rounded border object-fit-cover" style="width: 96px; height: 96px;"
                            alt="{{ $label }} logo">
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
