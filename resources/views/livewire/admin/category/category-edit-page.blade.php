<div class="container-xxl flex-grow-1 container-p-y">

    <form wire:submit="save">

        <div class="row">

            {{-- Left Side --}}
            <div class="col-lg-8">

                <div class="card">

                    <div class="card-header">
                        <h5 class="mb-1">Category Details</h5>
                        <small class="text-body-secondary">
                            Update category information in all supported languages.
                        </small>
                    </div>

                    <div class="card-body">

                        {{-- Cuisine --}}
                        <div class="mb-4">
                            <label class="form-label">
                                Cuisine
                            </label>

                            <select wire:model="cuisine_id"
                                class="form-select @error('cuisine_id') is-invalid @enderror">

                                <option value="">
                                    Select Cuisine
                                </option>

                                @foreach ($cuisines as $cuisine)
                                    <option value="{{ $cuisine->id }}">
                                        {{ $cuisine->getTranslation('name', 'en') }}
                                        |
                                        {{ $cuisine->getTranslation('name', 'ru') }}
                                        |
                                        {{ $cuisine->getTranslation('name', 'tg') }}
                                    </option>
                                @endforeach

                            </select>

                            @error('cuisine_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Language Tabs --}}
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <button id="btn-en" type="button" class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#tab-en">
                                    🇺🇸 English
                                </button>
                            </li>
                            <li class="nav-item">
                                <button id="btn-ru" type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-ru">
                                    🇷🇺 Russian
                                </button>
                            </li>

                            <li class="nav-item">
                                <button id="btn-tg" type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-tg">
                                    🇹🇯 Tajik
                                </button>
                            </li>
                        </ul>

                        {{-- Tabs --}}
                        <div class="tab-content">
                            {{-- English --}}
                            <div class="tab-pane fade show active" id="tab-en">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Category Name (English)
                                    </label>

                                    <input type="text" wire:model.blur="name.en"
                                        class="form-control @error('name.en') is-invalid @enderror">

                                    @error('name.en')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Description (English)
                                    </label>

                                    <textarea rows="4" wire:model.blur="description.en"
                                        class="form-control @error('description.en') is-invalid @enderror"></textarea>

                                    @error('description.en')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            {{-- Russian --}}
                            <div class="tab-pane fade" id="tab-ru">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Category Name (Russian)
                                    </label>

                                    <input type="text" wire:model.blur="name.ru"
                                        class="form-control @error('name.ru') is-invalid @enderror">

                                    @error('name.ru')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Description (Russian)
                                    </label>

                                    <textarea rows="4" wire:model.blur="description.ru"
                                        class="form-control @error('description.ru') is-invalid @enderror"></textarea>

                                    @error('description.ru')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            {{-- Tajik --}}
                            <div class="tab-pane fade" id="tab-tg">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Category Name (Tajik)
                                    </label>

                                    <input type="text" wire:model.blur="name.tg"
                                        class="form-control @error('name.tg') is-invalid @enderror">

                                    @error('name.tg')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Description (Tajik)
                                    </label>

                                    <textarea rows="4" wire:model.blur="description.tg"
                                        class="form-control @error('description.tg') is-invalid @enderror"></textarea>

                                    @error('description.tg')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Side --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            Publish
                        </h5>
                    </div>

                    <div class="card-body">

                        {{-- Status --}}
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="status" wire:model="status">
                            <label class="form-check-label" for="status">
                                Active
                            </label>
                        </div>

                        {{-- Image --}}
                        <div class="mb-4">
                            <label class="form-label">
                                Category Image
                            </label>

                            <input type="file" wire:model="image"
                                class="form-control @error('image') is-invalid @enderror">
                            @error('image')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div wire:loading wire:target="image" class="small text-muted mt-2">
                                Uploading...
                            </div>

                            {{-- Preview --}}
                            <div class="mt-3">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded border">
                                @elseif($category->image)
                                    <img src="{{ $category->image_url }}"
                                        class="img-fluid rounded border">
                                @endif
                            </div>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="icon-base ti tabler-device-floppy"></i>
                            Update Category
                        </button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-label-secondary w-100 mt-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {

            Livewire.on('switch-tab-category', (event) => {

                const tab = event.tab;

                const button = document.getElementById(`btn-${tab}`);

                if (button) {
                    bootstrap.Tab.getOrCreateInstance(button).show();
                }
            });

        });
    </script>
@endpush
