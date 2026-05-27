{{-- File-video fallback for emails — link only, no <video> tag (not supported in clients). --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="background-color:#000000; border-radius:8px; padding:20px 40px;">
            <a href="{!! $videoUrl !!}" target="_blank"
               style="color:#ffffff; text-decoration:none; font-size:16px; font-weight:600;">
                &#9654; {{ $label }}
            </a>
        </td>
    </tr>
</table>
