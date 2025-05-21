<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Wiki\KnowledgePage;
use Anonimatrix\PageEditor\Services\KnowledgeService;
use Kompo\Form;

class OpenWikiButton extends Form
{
    public $model = KnowledgePage::class;

    public function created()
    {
        $this->model(\Anonimatrix\PageEditor\Services\KnowledgeService::getCurrentRouteArticle());
    }

    public function render()
    {
        return _Link()->icon(_Sax('lifebuoy',30))->selfGet('getArticleWiki')->inDrawer()->id('wiki-help-modal')->class('text-gray-800 text-2xl');
    }

    public function getArticleWiki()
    {
        if (KnowledgeService::getCountCurrentRouteArticles(getReferrerRoute()) > 1) {
            return new \Anonimatrix\PageEditor\Components\Wiki\ArticlePage(null, [
                'just_related_to_route' => getReferrerRoute(),
            ]);
        }

        return new \Anonimatrix\PageEditor\Components\Wiki\ArticlePage($this->model->id);
    }
}
