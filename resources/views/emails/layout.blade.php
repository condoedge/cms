<!DOCTYPE html>
<html lang="{{ $lang }}" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <!--[if gte mso 9]>
    <xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelPerInch>96</o:PixelPerInch></o:OfficeDocumentSettings></xml>
    <![endif]-->
    <style type="text/css">
        /* Email Reset */
        body, #bodyTable { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; display: block; -ms-interpolation-mode: bicubic; max-width: 100%; }
        p, h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }
        a { color: {{ $linkColor }}; }
        body { background-color: {{ $bgColor }}; font-family: {!! $fontFamily !!}; font-size: {{ $fontSize }}px; color: {{ $textColor }}; }
        {!! $inlineCss !!}
    </style>
</head>
<body style="margin:0; padding:0; background-color:{{ $bgColor }}; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
<table id="bodyTable" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"
       style="background-color:{{ $bgColor }};">
    <tr>
        <td align="center" valign="top" style="padding:0;">
            <!--[if mso]>
            <table role="presentation" width="{{ $maxWidth }}" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr><td>
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                   style="max-width:{{ $maxWidth }}px; width:100%; background-color:{{ $contentBg }};
                          font-family:{!! $fontFamily !!}; font-size:{{ $fontSize }}px; color:{{ $textColor }};">
                <tr>
                    <td>{!! $content !!}</td>
                </tr>
            </table>
            <!--[if mso]>
                </td></tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
</body>
</html>
