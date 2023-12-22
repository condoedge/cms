<?php

namespace Anonimatrix\PageEditor\Support\Facades;

/**
 * @method static array getAvailableTypes()
 * @method static array getOptionsTypes()
 * @method static void setRoutes(string $route = 'crm/page/{page_id}/preview')
 * @method static object getComponent(string $name, string $default, array $args = [])
 * @method static object getPageInfoFormComponent($prefix = null, $pageItemId = null, $params = [])
 * @method static object getPageInfoFormComponent($pageItemId = null, $params = [])
 * @method static object getPageStyleFormComponent($prefix = null, $pageId = null, $params = [])
 * @method static object getPageStyleFormComponent($pageId = null, $params = [])
 * @method static object getPagePreviewComponent($prefix = null, $params = [])
 * @method static object getPagePreviewComponent($params = [])
 * @method static object getPageFormComponent($prefix = null, $pageId = null, $params = [])
 * @method static object getPageFormComponent($pageId = null, $params = [])
 * @method static object getPageDesignFormComponent($prefix = null, $pageId = null, $params = [])
 * @method static object getPageDesignFormComponent($pageId = null, $params = [])
 * @method static object getItemStylesFormComponent($prefix = null, $pageItemId = null, $params = [])
 * @method static object getItemStylesFormComponent($pageItemId = null, $params = [])
 *
 * @see \Anonimatrix\PageEditor\Services\PageEditorService
 */
class PageEditor extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-editor';
    }
}