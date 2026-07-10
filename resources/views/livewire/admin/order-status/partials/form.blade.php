<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.order_status_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        @include('livewire.admin.order-status.partials.translation-fields')
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.order_status_code') }}</label>
                        @if (! empty($readOnlyCode))
                            <input type="text" class="form-control" value="{{ $code }}" disabled>
                            <small class="text-body-secondary">{{ __('admin.order_status_code_system_hint') }}</small>
                        @else
                            <input type="text" class="form-control @error('code') is-invalid @enderror" wire:model.blur="code" placeholder="in_transit">
                            <small class="text-body-secondary">{{ __('admin.order_status_code_hint') }}</small>
                            @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.order_status_color') }}</label>
                        <select class="form-select @error('color') is-invalid @enderror" wire:model.live="color">
                            @foreach ($colors as $colorOption)
                                <option value="{{ $colorOption }}">{{ ucfirst($colorOption) }}</option>
                            @endforeach
                        </select>
                        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.order_status_sort_order') }}</label>
                        <input type="number" min="0" class="form-control @error('sortOrder') is-invalid @enderror" wire:model.blur="sortOrder">
                        @error('sortOrder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.order_status_description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" rows="3" wire:model.blur="description"></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        @php
                            $previewName = $name[app()->getLocale()] ?? $name['en'] ?? '';
                        @endphp
                        <span class="badge bg-label-{{ $color }}">{{ $previewName ?: __('admin.order_status_name') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.status') }}</h5></div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">{{ __('admin.active') }}</label>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save,update">{{ $submitLabel }}</span>
                        <span wire:loading wire:target="save,update">{{ __('admin.saving') }}...</span>
                    </button>
                    <a href="{{ route('admin.order-statuses.index') }}" class="btn btn-label-secondary">{{ __('admin.cancel') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
