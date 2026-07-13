@props([
    'href' => '#',
    'textClass' => 'app-brand-text demo menu-text fw-bold ms-3',
    'centered' => false,
])

@php
    $company = company();
@endphp

<a href="{{ $href }}" {{ $attributes->class(['app-brand-link', 'justify-content-center' => $centered]) }}>
    @if ($company['logo_url'])
        <span class="app-brand-logo demo">
            <img src="{{ $company['logo_url'] }}"
                 alt="{{ $company['name'] }}"
                 style="height: 32px; width: auto; max-width: 140px; object-fit: contain;">
        </span>
    @endif
    <span class="{{ $textClass }} {{ $company['logo_url'] ? 'ms-2' : '' }}">{{ $company['name'] }}</span>
</a>
