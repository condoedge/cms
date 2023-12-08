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
        Route::layout('layouts.main')->middleware(['auth'])->group(function() use ($route) {
            Route::get($route, \Anonimatrix\PageEditor\Components\Cms\PagePreview::class)->name('page.preview');
        });
    }

    public function getPageItemFormComponent(...$args)
    {
        return new (config('page-editor.components.page-item-form', \Anonimatrix\PageEditor\Components\Cms\PageItemForm::class))(...$args);
    }

    public function getPagePreviewComponent(...$args)
    {
        return new (config('page-editor.components.page-preview', \Anonimatrix\PageEditor\Components\Cms\PagePreview::class))(...$args);
    }

    public function getPageFormComponent(...$args)
    {
        return new (config('page-editor.components.page-content-form', \Anonimatrix\PageEditor\Components\Cms\PageContentForm::class))(...$args);
    }

    public function getPageDesignFormComponent(...$args)
    {
        return new (config('page-editor.components.page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class))(...$args);
    }

    public function getItemStylesFormComponent(...$args)
    {
        return new (config('page-editor.components.page-item-styles-form', \Anonimatrix\PageEditor\Components\Cms\StylePageItemForm::class))(...$args);
    }
}