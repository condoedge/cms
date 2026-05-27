<?php

use Anonimatrix\PageEditor\Http\Controllers\PageBlocksController;
use Anonimatrix\PageEditor\Http\ImageMethods;
use Illuminate\Support\Facades\Route;

// PageEditor::setRoutes(); // This will be called by user

Route::post('page-editor/get-image-size', [ImageMethods::class, 'getDefaultMaxWidth'])->name('page-editor.get-image-size');
Route::get('page-editor/get-full-view', [ImageMethods::class, 'getFullView'])->name('page-editor.get-full-view');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('page-editor/{page_id}/add-block/{block_type}', [PageBlocksController::class, 'addBlock'])
        ->name('page-editor.add-block');

    Route::get('page-editor/{page_id}/copy-block-form', [PageBlocksController::class, 'copyBlockForm'])
        ->name('page-editor.copy-block-form');
});

Route::get('page-editor/{page_id}/export-html', [PageBlocksController::class, 'exportHtml'])
    ->name('page-editor.export-html');
