<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

use Anonimatrix\PageEditor\Models\Abstracts\PageItemModel as AbstractsPageItemModel;

class PageItemModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return AbstractsPageItemModel::class;
    }
}