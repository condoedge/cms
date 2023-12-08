<?php

namespace Anonimatrix\PageEditor\Support\Facades;

class PageStyle extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-style-service';
    }
}