<?php

namespace Anonimatrix\PageEditor\Components\Wiki\Forms;

use Anonimatrix\PageEditor\Components\Cms\PageContentForm;
use Anonimatrix\PageEditor\Components\Wiki\ArticlePage;

class ArticlePageContentForm extends PageContentForm
{
    public $class = 'py-8';
    protected $prefixGroup = 'knowledge';

    public function created()
    {
        // Gate the editor on `wiki` WRITE when kompo/auth is installed; no-op without it.
        if (function_exists('checkAuthPermission')) {
            abort_unless(checkAuthPermission('wiki', \Kompo\Auth\Models\Teams\PermissionTypeEnum::WRITE), 403);
        }

        parent::created();
    }

    protected function top()
    {
        return _FlexBetween(
            _Link('cms::wiki.back-to-articles')->icon('arrow-left')->href('knowledge-list')->class('mb-4'),
            !$this->model->id ? null : _Link('cms::wiki.article-in-list')->knowledgeDrawer(ArticlePage::class, ['id' => $this->model->id])->class('mb-4'),
        );
    }
}