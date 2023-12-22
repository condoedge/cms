<?php

namespace Anonimatrix\PageEditor\Support\Facades;

/**
 * @method static void authorizationGuard(string $action, callable $callback)
 * @method static bool authorize(string $action, $model = null)
 *
 * @see \Anonimatrix\PageEditor\Services\PageItemService
 */
class PageItem extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-item';
    }
}