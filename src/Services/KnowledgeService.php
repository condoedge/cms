<?php

namespace Anonimatrix\PageEditor\Services;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Illuminate\Support\Facades\Route;

class KnowledgeService
{
    /**
     * Set the routes for the knowledge editor.
     */
    public static function setEditorRoute()
    {
        Route::get('knowledge-editor/{id?}', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticlePageContentForm::class)->name('knowledge.editor');
    }

    /**
     * Set route for the raw list of articles. Used for admin purposes.
     */
    public static function setRawListRoute()
    {
        Route::get('knowledge-list', \Anonimatrix\PageEditor\Components\Wiki\ArticleRawList::class)->name('knowledge.list');
    }

    /**
     * Set the routes for the articles. We have two routes:
     * 1. The articles list route and the article page route
     * 2. The what's new route
     */
    public static function setArticlesRoute()
    {
        Route::get('knowledge-articles/whats-new', \Anonimatrix\PageEditor\Components\Wiki\ArticlePage::class)->name('knowledge.whats-new');
        Route::get('knowledge-articles/{id?}', \Anonimatrix\PageEditor\Components\Wiki\ArticlePage::class)->name('knowledge.articles');
    }

    public static function setRenderDrawerRoute()
    {
        Route::get('knowledge-render-component/{know_component}/{know_locale}', \Anonimatrix\PageEditor\Components\Wiki\DynamicComponentRender::class)->name('knowledge.render-component');
    }

    public static function getCurrentRouteArticle($route = null)
    {
        $route = $route ?? request()->route()->getName();

        return PageModel::knowledgeAssociatedToRoute($route)->where('is_visible', 1)->first();
    }

    public static function getCountCurrentRouteArticles($route = null)
    {
        $route = $route ?? request()->route()->getName();

        return PageModel::knowledgeAssociatedToRoute($route)->where('is_visible', 1)->count();
    }
}
