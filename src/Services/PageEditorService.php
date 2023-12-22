<?php

namespace Anonimatrix\PageEditor\Services;

use Illuminate\Support\Facades\Route;

class PageEditorService
{
    /**
     * Get the available types from the configuration.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableTypes()
    {
        return collect(config('page-editor.types'))->filter(function ($item) {
            return !in_array($item, config('page-editor.hidden_types'));
        });
    }

    /**
     * Get the available types formatted for the select options.
     *
     * @return array
     */
    public function getOptionsTypes()
    {
        return collect($this->getAvailableTypes())->mapWithKeys(function ($item) {
            return [$item::ITEM_NAME => __($item::ITEM_TITLE)];
        })->toArray();
    }

    /**
     * Set the routes for the package.
     *
     * @param string $route
     * @return void
     */
    public function setRoutes($route = 'crm/page/{page_id}/preview')
    {
        // We need to remove layout
        Route::get($route, \Anonimatrix\PageEditor\Components\Cms\PagePreview::class)->name('page.preview');
    }

    /**
     * Get a visual component. (abstracted)
     *
     * @param string $name
     * @param mixed $default
     * @param array $args
     * @return mixed
     */
    protected function getComponent($name, $default, $args = [])
    {
        $prefixComponent = (count($args) > 0 && !is_numeric($args[0]) && !is_array($args[0])) ? ($args[0] . '.') : '';

        $otherArgs = (count($args) > 0 && $prefixComponent) ? array_slice($args, 1) : $args;

        return new (config('page-editor.components.' . $prefixComponent . $name, $default))(...$otherArgs);
    }

    /**
     * Get the page details form component.
     */
    public function getPageInfoFormComponent(...$args)
    {
        return $this->getComponent('page-info-form', \Anonimatrix\PageEditor\Components\Cms\PageInfoForm::class, $args);
    }

    /**
     * Get the page item form component.
     */
    public function getPageItemFormComponent(...$args)
    {
        return $this->getComponent('page-item-form', \Anonimatrix\PageEditor\Components\Cms\PageItemForm::class, $args);
    }

    /**
     * Get the page style form component.
     */
    public function getPageStyleFormComponent(...$args)
    {
        return $this->getComponent('page-style-form', \Anonimatrix\PageEditor\Components\Cms\PageStylingForm::class, $args);
    }

    /**
     * Get the page preview component.
     */
    public function getPagePreviewComponent(...$args)
    {
        return $this->getComponent('page-preview', \Anonimatrix\PageEditor\Components\Cms\PagePreview::class, $args);
    }

    /**
     * Get the complete page form with details and design forms.
     */
    public function getPageFormComponent(...$args)
    {
        return $this->getComponent('page-content-form', \Anonimatrix\PageEditor\Components\Cms\PageContentForm::class, $args);
    }

    /**
     * Get the page design (page-editor) form component.
     */
    public function getPageDesignFormComponent(...$args)
    {
        return $this->getComponent('page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class, $args);
    }

    /**
     * Get the page item styles form component.
     */
    public function getItemStylesFormComponent(...$args)
    {
        return $this->getComponent('page-item-styles-form', \Anonimatrix\PageEditor\Components\Cms\StylePageItemForm::class, $args);
    }
}