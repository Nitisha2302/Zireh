<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h4 class="mb-1">{{ $seller->full_name }}</h4>
            <p class="mb-0 text-body-secondary">{{ $seller->restaurant?->name }} | {{ $seller->verification_status }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success"
                wire:click="approveSeller">{{ __('admin.approve_seller') }}</button>
            <button type="button" class="btn btn-danger"
                wire:click="rejectSeller">{{ __('admin.reject_seller') }}</button>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-lg-4">
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.owner_details') }}</h5>
                </div>
                <div class="card-body">
                    @if ($seller->profile_photo)
                        <div class="mb-4">
                            <img src="{{ $seller->profile_photo_url }}" alt="{{ __('admin.seller_photo') }}"
                                class="img-fluid rounded ">
                        </div>
                    @endif
                    <p><strong>{{ __('admin.name') }}:</strong> {{ $seller->full_name }}</p>
                    <p><strong>{{ __('admin.phone') }}:</strong> {{ $seller->phone }}</p>
                    <p><strong>{{ __('admin.email') }}:</strong> {{ $seller->email }}</p>
                    <p><strong>{{ __('admin.status') }}:</strong> {{ $seller->status }}</p>
                    <p><strong>{{ __('admin.date_of_birth') }}:</strong> {{ $seller->dob }}</p>
                    <p><strong>{{ __('admin.address') }}:</strong> {{ $seller->address }}</p>
                    <p><strong>{{ __('admin.approved_at') }}:</strong>
                        {{ $seller->approved_at?->format('d M Y h:i A') ?: __('admin.not_available') }}</p>
                    <p><strong>{{ __('admin.rejected_at') }}:</strong>
                        {{ $seller->rejected_at?->format('d M Y h:i A') ?: __('admin.not_available') }}</p>
                    <p><strong>{{ __('admin.rejection_reason') }}:</strong>
                        {{ $seller->rejection_reason ?: __('admin.not_available') }}</p>
                    <p class="mb-0"><strong>{{ __('admin.last_login') }}:</strong>
                        {{ $seller->last_login_at?->format('d M Y h:i A') ?: __('admin.not_available') }}</p>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.restaurant_details') }}</h5>
                </div>
                <div class="card-body">
                    {{-- @if ($seller->restaurant?->logo)
                        <div class="mb-3">
                            <img src="{{ $seller->restaurant->logo_url }}" alt="{{ __('admin.restaurant_logo') }}"
                                class="img-fluid rounded">
                        </div>
                    @endif
                    @if ($seller->restaurant?->cover_image)
                        <div class="mb-3">
                            <img src="{{ $seller->restaurant->cover_image_url }}"
                                alt="{{ __('admin.restaurant_cover') }}" class="img-fluid rounded">
                        </div>
                    @endif --}}
                    <p><strong>{{ __('admin.name') }}:</strong> {{ $seller->restaurant?->name }}</p>
                    <p><strong>{{ __('admin.phone') }}:</strong> {{ $seller->restaurant?->phone }}</p>
                    <p><strong>{{ __('admin.email') }}:</strong>
                        {{ $seller->restaurant?->email ?: __('admin.not_available') }}</p>
                    <p><strong>{{ __('admin.address') }}:</strong> {{ $seller->restaurant?->address }}</p>
                    <p><strong>{{ __('admin.city') }}:</strong> {{ $seller->restaurant?->city }}</p>
                    <p><strong>{{ __('admin.latitude') }}:</strong>
                        {{ $seller->restaurant?->latitude ?: __('admin.not_available') }}</p>
                    <p><strong>{{ __('admin.longitude') }}:</strong>
                        {{ $seller->restaurant?->longitude ?: __('admin.not_available') }}</p>
                    <p><strong>{{ __('admin.cuisine') }}:</strong> {{ $seller->restaurant?->cuisine_type }}</p>
                    <p><strong>{{ __('admin.food_type') }}:</strong> {{ $seller->restaurant?->food_type }}</p>
                    <p><strong>{{ __('admin.min_order') }}:</strong> {{ $seller->restaurant?->minimum_order_amount }}
                    </p>
                    <p><strong>{{ __('admin.prep_time') }}:</strong>
                        {{ $seller->restaurant?->average_preparation_time }} {{ __('admin.minutes') }}</p>
                    <p><strong>{{ __('admin.delivery_radius') }}:</strong> {{ $seller->restaurant?->delivery_radius }}
                    </p>
                    <p><strong>{{ __('admin.restaurant_status') }}:</strong> {{ $seller->restaurant?->status }}</p>
                    <p class="mb-0"><strong>{{ __('admin.hours') }}:</strong>
                        {{ $seller->restaurant?->opening_hours }} - {{ $seller->restaurant?->closing_hours }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.change_seller_password') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label">{{ __('admin.new_password') }}</label>
                        <input type="password" class="form-control" wire:model="newPassword">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">{{ __('admin.confirm_password') }}</label>
                        <input type="password" class="form-control" wire:model="confirmPassword">
                    </div>
                    <button type="button" class="btn btn-primary"
                        wire:click="updateSellerPassword">{{ __('admin.update_password') }}</button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.uploaded_documents') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('admin.type') }}</th>
                                <th>{{ __('admin.status') }}</th>
                                <th>{{ __('admin.file') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($seller->documents as $document)
                                <tr>
                                    <td>{{ str($document->document_type)->replace('_', ' ')->title() }}</td>
                                    <td><span class="badge bg-label-info">{{ $document->status }}</span></td>
                                    <td><a href="{{ $document->file_path_url }}"
                                            target="_blank">{{ __('admin.open') }}</a></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-success"
                                            wire:click="approveDocument({{ $document->id }})">{{ __('admin.approve') }}</button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="rejectDocument({{ $document->id }})">{{ __('admin.reject') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-body-secondary">
                                        {{ __('admin.no_documents_uploaded') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.uploaded_documents_government_id') }}</h5>
                </div>

                <div class="card-body">
                    @if (!empty($seller->government_id_image_urls))
                        <div class="row g-3">

                            @foreach ($seller->government_id_image_urls as $imageUrl)
                                <div class="col-md-3 col-sm-4 col-6">

                                    <a href="{{ $imageUrl }}" target="_blank">
                                        <img src="{{ $imageUrl }}" alt="Government ID"
                                            class="img-fluid rounded border shadow-sm"
                                            style="height: 200px; width: 100%; object-fit: cover;">
                                    </a>

                                </div>
                            @endforeach

                        </div>
                    @else
                        <div class="text-center text-body-secondary py-4">
                            {{ __('admin.no_government_id_uploaded') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.didit_verification') }}</h5>
                </div>
                <div class="card-body">
                    @php($verification = $seller->verifications)

                    @if ($verification)
                        <p><strong>{{ __('admin.provider') }}:</strong> {{ $verification->provider }}</p>
                        <p><strong>{{ __('admin.session_id') }}:</strong> {{ $verification->session_id }}</p>
                        <p><strong>{{ __('admin.session_number') }}:</strong> {{ $verification->session_number }}
                        </p>
                        <p><strong>{{ __('admin.status') }}:</strong> {{ $verification->status }}</p>
                        <p><strong>{{ __('admin.submitted_at') }}:</strong>
                            {{ $verification->submitted_at?->format('d M Y h:i A') ?: __('admin.not_available') }}
                        </p>
                        <p><strong>{{ __('admin.reviewed_at') }}:</strong>
                            {{ $verification->reviewed_at?->format('d M Y h:i A') ?: __('admin.not_available') }}
                        </p>
                        <p><strong>{{ __('admin.session_url') }}:</strong> <a href="{{ $verification->session_url }}"
                                target="_blank">{{ __('admin.open_didit_session') }}</a></p>
                        @if ($verification->decision_payload)
                            <div class="mt-4">
                                <label class="form-label">{{ __('admin.decision_payload') }}</label>
                                <pre class="bg-light p-3 rounded small mb-0">{{ json_encode($verification->decision_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-success"
                                wire:click="markVerificationReviewed({{ $verification->id }}, 'approved')">{{ __('admin.mark_approved') }}</button>
                            <button type="button" class="btn btn-outline-danger"
                                wire:click="markVerificationReviewed({{ $verification->id }}, 'rejected')">{{ __('admin.mark_rejected') }}</button>
                            <button type="button" class="btn btn-outline-warning"
                                wire:click="markVerificationReviewed({{ $verification->id }}, 'in_review')">{{ __('admin.mark_in_review') }}</button>
                        </div>
                    @else
                        <p class="mb-0 text-body-secondary">{{ __('admin.no_didit_session') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
