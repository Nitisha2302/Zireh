<div class="card">
    <div class="card-header">
        <div class="placeholder-glow">
            <span class="placeholder col-3"></span>
            <span class="placeholder col-5 ms-3"></span>
        </div>
    </div>
    <div class="card-body">
        <div class="placeholder-glow mb-4">
            <span class="placeholder col-12" style="height: 40px;"></span>
        </div>
        @for ($row = 0; $row < 5; $row++)
            <div class="d-flex align-items-center gap-3 py-3 border-bottom placeholder-glow">
                <span class="placeholder rounded" style="width: 48px; height: 48px;"></span>
                <div class="flex-grow-1">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-8 d-block mt-2"></span>
                </div>
                <span class="placeholder col-2"></span>
            </div>
        @endfor
        <span class="visually-hidden">{{ $title }} {{ __('admin.loading') }}</span>
    </div>
</div>
