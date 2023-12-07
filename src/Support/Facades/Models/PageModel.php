<?php

namespace Anonimatrix\PageEditor\Support\Facades\Models;

use Anonimatrix\PageEditor\Models\Abstracts\PageModel as AbstractsPageModel;

class PageModel extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return AbstractsPageModel::class;
    }
}