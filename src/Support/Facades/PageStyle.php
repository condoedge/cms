<?php

namespace Anonimatrix\PageEditor\Support\Facades;

/**
 * @method static void setStylesToModel($model, array $styles)
 *
 * @see \Anonimatrix\PageEditor\Services\PageStyleService
 */
class PageStyle extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-style-service';
    }
}