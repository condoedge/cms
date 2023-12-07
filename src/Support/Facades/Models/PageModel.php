<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

class PageModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-model';
    }
}