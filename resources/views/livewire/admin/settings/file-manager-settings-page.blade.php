<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.file_manager_settings') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.file_manager_description') }}</p>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.storage_driver') }}</label>
                        <select class="form-select" wire:model.live="file_upload_disk">
                            <option value="local">{{ __('admin.local') }}</option>
                            <option value="s3">AWS S3</option>
                        </select>
                    </div>

                    @if ($file_upload_disk === 's3')
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.access_key') }}</label>
                            <input type="text" class="form-control" wire:model="file_s3_key">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.secret_key') }}</label>
                            <input type="password" class="form-control" wire:model="file_s3_secret">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.region') }}</label>
                            <input type="text" class="form-control" wire:model="file_s3_region">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.bucket') }}</label>
                            <input type="text" class="form-control" wire:model="file_s3_bucket">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.custom_url') }}</label>
                            <input type="url" class="form-control" wire:model="file_s3_url" placeholder="https://cdn.example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.endpoint') }}</label>
                            <input type="url" class="form-control" wire:model="file_s3_endpoint" placeholder="https://s3.ap-south-1.amazonaws.com">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="file_s3_use_path_style_endpoint" wire:model="file_s3_use_path_style_endpoint">
                                <label class="form-check-label" for="file_s3_use_path_style_endpoint">
                                    {{ __('admin.use_path_style_endpoint') }}
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save_file_manager_settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
