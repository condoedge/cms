<?php

namespace Anonimatrix\PageEditor\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class KnowledgeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'knowledge');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'knowledge');

        $this->publishes([
            __DIR__ . '/../../migrations/' => database_path('migrations/knowledge'),
        ], 'knowledge');

        // Config settings. Used to get components like this: PageEditor::getPageContentComponent('knowledge');
        Config::set('page-editor.components.knowledge.page-content-form', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticlePageContentForm::class);
        Config::set('page-editor.components.knowledge.page-info-form', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticleInfoForm::class);
        Config::set('page-editor.components.knowledge.page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class);
    }
}
