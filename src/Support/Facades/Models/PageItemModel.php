<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

/**
 * @see \Anonimatrix\PageEditor\Models\PageItem
 */
class PageItemModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-item-model';
    }
}