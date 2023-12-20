<?php

namespace Anonimatrix\PageEditor\Services;

use Illuminate\Support\Facades\Route;

class PageEditorService
{
    public function getAvailableTypes()
    {
        return collect(config('page-editor.types'))->filter(function ($item) {
            return !in_array($item, config('page-editor.hidden_types'));
        });
    }

    public function getOptionsTypes()
    {
        return collect($this->getAvailableTypes())->mapWithKeys(function ($item) {
            return [$item::ITEM_NAME => __($item::ITEM_TITLE)];
        })->toArray();
    }

    public function setRoutes($route = 'crm/page/{page_id}/preview')
    {
        // We need to remove layout
        Route::get($route, \Anonimatrix\PageEditor\Components\Cms\PagePreview::class)->name('page.preview');
    }

    public function getComponent($name, $default, $args = [])
    {
        $prefixComponent = (count($args) > 0 && !is_numeric($args[0]) && !is_array($args[0])) ? ($args[0] . '.') : '';

        $otherArgs = (count($args) > 0 && $prefixComponent) ? array_slice($args, 1) : $args;

        return new (config('page-editor.components.' . $prefixComponent . $name, $default))(...$otherArgs);
    }

    public function getPageInfoFormComponent(...$args)
    {
        return $this->getComponent('page-info-form', \Anonimatrix\PageEditor\Components\Cms\PageInfoForm::class, $args);
    }

    public function getPageItemFormComponent(...$args)
    {
        return $this->getComponent('page-item-form', \Anonimatrix\PageEditor\Components\Cms\PageItemForm::class, $args);
    }

    public function getPageStyleFormComponent(...$args)
    {
        return $this->getComponent('page-style-form', \Anonimatrix\PageEditor\Components\Cms\PageStylingForm::class, $args);
    }

    public function getPagePreviewComponent(...$args)
    {
        return $this->getComponent('page-preview', \Anonimatrix\PageEditor\Components\Cms\PagePreview::class, $args);
    }

    public function getPageFormComponent(...$args)
    {
        return $this->getComponent('page-content-form', \Anonimatrix\PageEditor\Components\Cms\PageContentForm::class, $args);
    }

    public function getPageDesignFormComponent(...$args)
    {
        return $this->getComponent('page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class, $args);
    }

    public function getItemStylesFormComponent(...$args)
    {
        return $this->getComponent('page-item-styles-form', \Anonimatrix\PageEditor\Components\Cms\StylePageItemForm::class, $args);
    }
}