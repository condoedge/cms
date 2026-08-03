<?php

namespace Anonimatrix\PageEditor\Components\Wiki\Forms;

use Anonimatrix\PageEditor\Components\Cms\PageContentForm;
use Anonimatrix\PageEditor\Components\Cms\WikiEditorLayout;

class ArticlePageContentForm extends PageContentForm
{
    public $class = 'py-8';
    protected $prefixGroup = 'knowledge';

    public function pageEditorRender()
    {
        return new WikiEditorLayout($this->model?->id, [
            'prefix_group' => $this->prefixGroup,
        ]);
    }
}