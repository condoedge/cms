<?php

namespace Anonimatrix\PageEditor\Support\Facades\Features;

/**
 * @method static void addFeature(string $feature)
 * @method static bool hasFeature(string $feature)
 *
 * @see \Anonimatrix\PageEditor\Features\FeaturesService
 */
class Features extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-editor-features';
    }
}