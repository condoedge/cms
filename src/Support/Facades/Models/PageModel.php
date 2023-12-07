<?php

namespace Anonimatrix\PageEditor\Support\Facades;

use Anonimatrix\PageEditor\Models\Abstracts\PageModel as AbstractsPageModel;

class PageModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return AbstractsPageModel::class;
    }
}