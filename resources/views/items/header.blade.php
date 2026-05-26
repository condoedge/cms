{{--
    Image header for emails. MSO uses VML <v:rect> with background fill; other
    clients use a table with CSS background-image. Overlay rgba applied as
    background-color on the inner <td> (set $overlayBg='' to disable).
--}}
<!--[if mso]>
<v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false"
        style="width:600px;height:{{ $height }}px;">
    <v:fill type="frame" src="{!! $imageUrl !!}" />
    <v:textbox inset="0,0,0,0" style="mso-fit-shape-to-text:false">
<![endif]-->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-image: url('{{ $imageUrl }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <tr>
        <td height="{{ $height }}" align="center" valign="{{ $verticalAlign }}"
            style="padding: 20px; color: {{ $textColor }}; font-size: 1.5rem; text-align: center;@if($overlayBg) background-color: {{ $overlayBg }};@endif">
            {!! $title !!}
        </td>
    </tr>
</table>
<!--[if mso]>
    </v:textbox>
</v:rect>
<![endif]-->
