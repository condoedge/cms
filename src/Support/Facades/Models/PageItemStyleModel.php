<?php

namespace Anonimatrix\PageEditor\Support\Facades;

use Anonimatrix\PageEditor\Models\Abstracts\PageItemStyleModel as AbstractsPageItemStyleModel;

class PageItemStyleModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return AbstractsPageItemStyleModel::class;
    }
}