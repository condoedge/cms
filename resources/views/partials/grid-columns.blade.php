{{-- Email grid row: side-by-side <td> columns that stack on mobile, scoped by the table's unique class. --}}
<style>@media (max-width: 600px) { .{{ $uniqueId }} td { display: block !important; width: 100% !important; } }</style>
<!--[if mso]>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="{{ $colWidthPx }}" valign="top">
<![endif]-->
<table role="presentation" class="{{ $uniqueId }}" width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed;">
    <tr>{!! $columns !!}</tr>
</table>
<!--[if mso]>
</td></tr></table>
<![endif]-->
