{{-- Thumbnail-with-link rendering for YouTube/Vimeo in emails. iframes aren't supported in mail clients. --}}
<a href="{!! $videoUrl !!}" target="_blank" style="text-decoration:none; display:block; position:relative;">
    <img src="{!! $thumbnailUrl !!}"
         alt="{{ $alt }}"
         width="600"
         style="width:100%; max-width:100%; height:auto; display:block; border:0; {!! $videoStyles !!}" />
</a>
