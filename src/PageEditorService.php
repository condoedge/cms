<?php

namespace Anonimatrix\PageEditor;

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
            Route::get($route, \Anonimatrix\PageEditor\Components\PagePreview::class)->name('page.preview');
        });
    }
}