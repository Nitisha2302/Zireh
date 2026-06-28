<ul class="nav nav-tabs mb-4" role="tablist">
    @foreach (['en' => 'English', 'ru' => 'Russian', 'tg' => 'Tajik'] as $locale => $label)
        <li class="nav-item" role="presentation">
            <button id="btn-{{ $locale }}" type="button" class="nav-link {{ $loop->first ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#tab-{{ $locale }}">
                {{ $label }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    @foreach (['en' => 'English', 'ru' => 'Russian', 'tg' => 'Tajik'] as $locale => $label)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $locale }}" role="tabpanel">
            <div class="mb-0">
                <label class="form-label" for="name-{{ $locale }}">Category name ({{ $label }})</label>
                <input id="name-{{ $locale }}" type="text"
                    class="form-control @error("name.$locale") is-invalid @enderror"
                    wire:model.blur="name.{{ $locale }}" placeholder="Category name in {{ $label }}">
                @error("name.$locale")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endforeach
</div>
