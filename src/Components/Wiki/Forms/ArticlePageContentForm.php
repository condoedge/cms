<?php

namespace Anonimatrix\PageEditor\Components\Wiki\Forms;

use Anonimatrix\PageEditor\Components\Cms\PageContentForm;
use Anonimatrix\PageEditor\Components\Wiki\ArticlePage;
use Anonimatrix\PageEditor\Components\Wiki\ArticleRawList;

class ArticlePageContentForm extends PageContentForm
{
    public $class = 'py-8';
    protected $prefixGroup = 'knowledge';

    protected function top()
    {
        return _FlexBetween(
            _Link('cms::wiki.back-to-articles')->icon('arrow-left')->href('knowledge-list')->class('mb-4'),
            !$this->model->id ? null : _Link('cms::wiki.article-in-list')->knowledgeDrawer(ArticlePage::class, ['id' => $this->model->id])->class('mb-4'),
        );
    }
}