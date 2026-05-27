{{-- Email-safe wrapper that aligns its content (left/center/right) inside a 100%-wide table cell. --}}
<table role="presentation" width="{{ $width }}" border="0" cellspacing="0" cellpadding="0" style="{!! $tableStyles !!}">
    <tr>
        <td align="{{ $align }}" style="{!! $cellStyles !!}"@if($bgColor) bgcolor="{{ $bgColor }}"@endif>
            {!! $content !!}
        </td>
    </tr>
</table>
