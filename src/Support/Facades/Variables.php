<?php

namespace Anonimatrix\PageEditor\Support\Facades;

class Variables extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-editor-variables';
    }
}