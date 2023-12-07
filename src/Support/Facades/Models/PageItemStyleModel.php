<?php

namespace Anonimatrix\PageEditor\Support\Facades;

use Anonimatrix\PageEditor\Interfaces\PageItemStyleInterface;

class PageItemStyleModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return PageItemStyleInterface::class;
    }
}