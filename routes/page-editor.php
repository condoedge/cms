<?php

use Anonimatrix\PageEditor\Http\ImageMethods;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Illuminate\Support\Facades\Route;

// PageEditor::setRoutes(); // This will be called by user

Route::post('page-editor/get-image-size', [ImageMethods::class, 'getDefaultMaxWidth'])->name('page-editor.get-image-size');
Route::get('page-editor/get-full-view', [ImageMethods::class, 'getFullView'])->name('page-editor.get-full-view');