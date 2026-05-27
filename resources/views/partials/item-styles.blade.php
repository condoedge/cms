{{-- Per-item CSS rules scoped by the item's unique ID. All sections optional. --}}
@php
    $hasAny = $mobileStyles || $hideOnMobile || $hideOnDesktop;
@endphp
@if ($hasAny)
<style>
    @if ($mobileStyles)
    @media (max-width: 768px) { #{{ $uniqueId }} { {!! $mobileStyles !!} } }
    @endif
    @if ($hideOnMobile)
    @media (max-width: 600px) { #{{ $uniqueId }} { display: none !important; mso-hide: all !important; } }
    @endif
    @if ($hideOnDesktop)
    @media (min-width: 601px) { #{{ $uniqueId }} { display: none !important; mso-hide: all !important; } }
    @endif
</style>
@endif
