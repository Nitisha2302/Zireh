<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="save">
        <div class="row">
            {{-- Left Side --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">Cuisine Details</h5>
                        <small class="text-body-secondary">
                            Enter cuisine names in all supported languages.
                        </small>
                    </div>

                    <div class="card-body">
                        {{-- Language Tabs --}}
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button id="btn-en" type="button" class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#tab-en">
                                    🇺🇸 English
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button id="btn-ru" type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-ru">
                                    🇷🇺 Russian
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button id="btn-tg" type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-tg">
                                    🇹🇯 Tajik
                                </button>
                            </li>
                        </ul>

                        {{-- Tab Content --}}
                        <div class="tab-content">
                            {{-- English --}}
                            <div class="tab-pane fade show active" id="tab-en" role="tabpanel">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Cuisine Name (English)
                                    </label>

                                    <input type="text" class="form-control @error('name.en') is-invalid @enderror"
                                        wire:model.blur="name.en" placeholder="Indian Cuisine">

                                    @error('name.en')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            {{-- Russian --}}
                            <div class="tab-pane fade" id="tab-ru" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Cuisine Name (Russian)
                                    </label>

                                    <input type="text" class="form-control @error('name.ru') is-invalid @enderror"
                                        wire:model.blur="name.ru" placeholder="Индийская кухня">

                                    @error('name.ru')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            {{-- Tajik --}}
                            <div class="tab-pane fade" id="tab-tg" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Cuisine Name (Tajik)
                                    </label>

                                    <input type="text" class="form-control @error('name.tg') is-invalid @enderror"
                                        wire:model.blur="name.tg" placeholder="Таомҳои Ҳиндӣ">

                                    @error('name.tg')
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
                        <h5 class="mb-0">Publish</h5>
                    </div>

                    <div class="card-body">
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="status" wire:model="status">
                            <label class="form-check-label" for="status">
                                Active
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                Cuisine Image
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

                            @if ($image)
                                <div class="mt-3">
                                    <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded border"
                                        alt="Cuisine image preview">
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="icon-base ti tabler-device-floppy"></i>
                            Save Cuisine
                        </button>

                        <a href="{{ route('admin.platform.index') }}" class="btn btn-label-secondary w-100 mt-2">
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
        Livewire.on('switch-tab', (event) => {
            const tab = event.tab;
            const button = document.getElementById(`btn-${tab}`);
            console.log('Switching to tab:', tab);

            if (button) {
                bootstrap.Tab.getOrCreateInstance(button).show();
            }
        });
    </script>
@endpush
