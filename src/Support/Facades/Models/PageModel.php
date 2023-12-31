<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

/**
 * @see \Anonimatrix\PageEditor\Models\Page
 */
class PageModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-model';
    }
}