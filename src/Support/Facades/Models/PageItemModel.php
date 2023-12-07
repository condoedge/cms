<?php

namespace Anonimatrix\PageEditor\Support\Facades;

use Anonimatrix\PageEditor\Interfaces\PageItemInterface;

class PageItemModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return PageItemInterface::class;
    }
}