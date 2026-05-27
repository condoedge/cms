{{-- Image for emails: wrapped in <a> if a link URL is provided, otherwise bare <img>. Caller already escaped src/alt/href. --}}
@if ($linkUrl)
    <a href="{!! $linkUrl !!}" target="_blank" style="text-decoration:none; outline:none; border:none;">
        <img src="{!! $imageUrl !!}"
             alt="{!! $altText !!}"
             width="{{ $widthAttr }}"
             height="{{ $heightAttr }}"
             style="{!! $imgStyles !!} display:block; border:0; outline:none; text-decoration:none;" />
    </a>
@else
    <img src="{!! $imageUrl !!}"
         alt="{!! $altText !!}"
         width="{{ $widthAttr }}"
         height="{{ $heightAttr }}"
         style="{!! $imgStyles !!} display:block; border:0; outline:none; text-decoration:none;" />
@endif
