@php
    $brandIconUrl = $siteBrandIconUrl ?? $siteBrowserIconUrl ?? null;
@endphp
<a class="storefront-brand" href="{{ $href }}" aria-label="{{ $siteName }}">
    @if (!empty($brandIconUrl))
        <span class="storefront-brand__mark storefront-brand__mark--image">
            <img src="{{ $brandIconUrl }}" alt="" loading="eager" decoding="async">
        </span>
    @else
        <span class="storefront-brand__mark">C</span>
    @endif
    <span class="storefront-brand__name">{{ $siteName }}</span>
</a>
