<?php

namespace Anonimatrix\PageEditor\Support\Facades;

class PageEditor extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-editor';
    }
}