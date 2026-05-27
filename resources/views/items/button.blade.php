{{--
    Email-safe button. MSO conditional for Outlook (VML <v:roundrect>),
    standard <a> for everything else. All values pre-escaped in the PHP caller.
--}}
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
    <tr>
        <td align="center" style="padding:10px 0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:{{ $buttonWidth }};">
                <tr>
                    <td align="center" bgcolor="{{ $bgColor }}" style="background-color:{{ $bgColor }}; border-radius:{{ $borderRadius }}px; text-align:center;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                                     href="{!! $href !!}"
                                     style="height:45px;v-text-anchor:middle;width:{{ $buttonWidthPx }}px;"
                                     arcsize="{{ $arcsize }}%"
                                     fillcolor="{{ $bgColor }}"
                                     stroke="f">
                            <w:anchorlock/>
                            <center style="color:{{ $textColor }}; font-size:{{ $fontSize }}px; font-weight:600;">{!! $title !!}</center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                        <a href="{!! $href !!}" target="_blank"
                           style="display:inline-block; width:100%; padding:{{ $padding }};
                                  color:{{ $textColor }}; background-color:{{ $bgColor }};
                                  font-size:{{ $fontSize }}px; font-weight:600;
                                  text-align:center; text-decoration:none;
                                  border-radius:{{ $borderRadius }}px;
                                  -webkit-text-size-adjust:none; mso-hide:all;">{!! $title !!}</a>
                        <!--<![endif]-->
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
