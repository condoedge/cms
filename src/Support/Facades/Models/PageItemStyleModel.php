<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

use Anonimatrix\PageEditor\Models\Abstracts\PageItemStyleModel as AbstractsPageItemStyleModel;

class PageItemStyleModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return AbstractsPageItemStyleModel::class;
    }
}