<div>
    <div id="loading-{{ $uniqueId }}" style="display:flex; justify-content:center; margin-top:50px;">
        {!! $spinner !!}
    </div>
    <iframe id="iframe-{{ $uniqueId }}"
            src="{!! $scribeEmbedUrl !!}"
            width="100%"
            frameborder="0"
            height="{{ $height }}"></iframe>
</div>
<script>
    (function () {
        var spinnerEl = document.getElementById('loading-{{ $uniqueId }}');
        var iframeEl = document.getElementById('iframe-{{ $uniqueId }}');
        if (!iframeEl || !spinnerEl) return;
        iframeEl.addEventListener('load', function () {
            spinnerEl.style.transition = 'opacity 0.3s';
            spinnerEl.style.opacity = '0';
            setTimeout(function () { spinnerEl.style.display = 'none'; }, 300);
        });
    })();
</script>
