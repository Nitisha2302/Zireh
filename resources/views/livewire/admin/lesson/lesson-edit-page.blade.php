<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="update">
        @include('livewire.admin.lesson.partials.form', ['record' => $lesson])
    </form>
</div>
@include('livewire.admin.platform.partials.tab-script')
