<div>
    <div id="loading-{{ $uniqueId }}" style="display:flex; justify-content:center; margin-top:50px; margin-bottom:20px;">
        {!! $spinner !!}
    </div>
    <iframe id="iframe-{{ $uniqueId }}"
            onload="var spinnerEl=document.getElementById('loading-{{ $uniqueId }}');if(!spinnerEl){return;}spinnerEl.style.transition='opacity 0.1s';spinnerEl.style.opacity='0';setTimeout(function(){spinnerEl.style.display='none';},300);"
            src="{!! $scribeEmbedUrl !!}"
            width="100%"
            frameborder="0"
            height="{{ $height }}"></iframe>
</div>
