<video style="width:100%; {!! $videoStyles !!}"
       class="{{ $classes }}"
       autoplay
       loop
       muted
       playsinline
       controlslist="nodownload,nofullscreen,noremoteplayback">
    <source src="{!! $src !!}" type="video/mp4">
    Your browser does not support the video tag.
</video>
