@push('scripts')
    <script>
        Livewire.on('switch-tab', (event) => {
            const button = document.getElementById(`btn-${event.tab}`);
            if (button) bootstrap.Tab.getOrCreateInstance(button).show();
        });
    </script>
@endpush
