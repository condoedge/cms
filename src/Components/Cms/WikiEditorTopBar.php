<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Components\Cms\EditorTopBar;

class WikiEditorTopBar extends EditorTopBar
{
    protected function backButton()
    {
        return _Link()->icon('arrow-left')
            ->class('vlEditorTopBarBack')
            ->attr(['aria-label' => __('cms::cms.back')])
            ->href('knowledge-list');
    }
}