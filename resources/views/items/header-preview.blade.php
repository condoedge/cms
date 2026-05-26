{{-- Editor preview for HeaderItem: background image + optional overlay + centered title. --}}
<div style="position:relative; width:100%; height:{{ $height }}px;
            @if($imageUrl) background-image:url('{{ $imageUrl }}'); @endif
            background-size:{{ $bgSize }}; background-position:center; background-repeat:no-repeat;
            display:flex; align-items:{{ $textPosition }}; justify-content:center;">
    @if ($hasOverlay)
        <div style="position:absolute; top:0; left:0; width:100%; height:100%;
                    background-color:{{ $overlayColor }}; opacity:{{ $overlayOpacity }};"></div>
    @endif
    <div style="position:relative; z-index:1; color:{{ $textColor }};
                text-align:center; font-size:1.5rem; padding:20px;">{{ $title }}</div>
</div>
