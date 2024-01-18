<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Wiki\KnowledgePage;
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
        return _Link()->icon('question-mark-circle')->selfGet('getArticleWiki')->inDrawer()->id('wiki-help-modal')->class('text-gray-800 text-2xl');
    }

    public function getArticleWiki()
    {
        return new \Anonimatrix\PageEditor\Components\Wiki\ArticlePage($this->model->id);
    }
}