<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.didit_settings') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.didit_settings_description') }}</p>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.base_url') }}</label>
                        <input type="url" class="form-control" wire:model="didit_base_url">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.api_key') }}</label>
                        <input type="text" class="form-control" wire:model="didit_api_key">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.workflow_id') }}</label>
                        <input type="text" class="form-control" wire:model="didit_workflow_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.callback_url') }}</label>
                        <input type="url" class="form-control" wire:model="didit_callback_url">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save_didit_settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
