<?php

if (!function_exists('_CKEditorPageItem')) {
    function _CKEditorPageItem()
    {
        return \Anonimatrix\PageEditor\Components\CKEditorPageItem::form(...func_get_args());
    }
}
