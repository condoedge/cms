<?php

namespace Anonimatrix\PageEditor\Support\Facades\Features;

/**
 * @method static void setVariables(callable $callback, string $section = 'default')
 * @method static \Anonimatrix\PageEditor\Components\Link link(string $label, string $type, string $class = null)
 * @method static mixed getVariables(string $section = 'default')
 *
 * @see \Anonimatrix\PageEditor\Services\EditorVariablesService
 */
class Variables extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-editor-variables';
    }
}