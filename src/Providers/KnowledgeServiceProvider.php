<?php

namespace Anonimatrix\PageEditor\Providers;

use Anonimatrix\PageEditor\Components\Wiki\DynamicComponentRender;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Kompo\Link;

class KnowledgeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/wiki' => database_path('migrations/wiki'),
        ], 'wiki');

        // Config settings. Used to get components like this: PageEditor::getPageContentComponent('knowledge');
        Config::set('page-editor.components.knowledge.page-content-form', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticlePageContentForm::class);
        Config::set('page-editor.components.knowledge.page-info-form', \Anonimatrix\PageEditor\Components\Wiki\Forms\ArticleInfoForm::class);
        Config::set('page-editor.components.knowledge.page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class);
    
        $this->setMacros();

        $this->loadRoutes();
    }

    public function setMacros()
    {
        Link::macro('knowledgeDrawer', function ($component, $props = []) {
            $componentKey = array_search($component, DynamicComponentRender::AVAILABLE_COMPONENTS);

            $props['know_component'] = $componentKey;
            $props['know_locale'] = session('kompo_locale');

            return $this->get(route('knowledge.render-component', $props))->inDrawer()->closeDrawer();
        });
    }

    public function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/knowledge.php');
    }
}
