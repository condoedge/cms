<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('knowledge-editor/{id?}', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticlePageContentForm::class)->name('knowledge.editor');
Route::get('knowledge-articles/{id?}', \Anonimatrix\PageEditor\Components\Wiki\ArticlePage::class)->name('knowledge.articles');