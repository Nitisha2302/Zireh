<ul class="nav nav-tabs mb-4" role="tablist">
    @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
        <li class="nav-item" role="presentation">
            <button id="btn-{{ $locale }}" type="button" class="nav-link {{ $loop->first ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#tab-{{ $locale }}">
                {{ $label }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    @foreach (['en' => __('admin.english'), 'ru' => __('admin.russian'), 'tg' => __('admin.tajik')] as $locale => $label)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $locale }}" role="tabpanel">
            <div class="mb-4">
                <label class="form-label" for="title-{{ $locale }}">{{ __('admin.title_in', ['language' => $label]) }}</label>
                <input
                    id="title-{{ $locale }}"
                    type="text"
                    class="form-control @error("title.$locale") is-invalid @enderror"
                    wire:model.blur="title.{{ $locale }}"
                    placeholder="{{ __('admin.title_in', ['language' => $label]) }}"
                >
                @error("title.$locale")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-0">
                <label class="form-label" for="description-{{ $locale }}">{{ __('admin.description_in', ['language' => $label]) }}</label>
                <textarea
                    id="description-{{ $locale }}"
                    rows="5"
                    class="form-control @error("description.$locale") is-invalid @enderror"
                    wire:model.blur="description.{{ $locale }}"
                    placeholder="{{ __('admin.description_in', ['language' => $label]) }}"
                ></textarea>
                @error("description.$locale")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endforeach
</div>
