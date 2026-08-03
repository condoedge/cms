<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Components\Cms\PageEditorLayout;

class WikiEditorLayout extends PageEditorLayout
{
    protected function topBar()
    {
        return new WikiEditorTopBar($this->model->id, [
            'prefix_group' => $this->prefixGroup,
        ]);
    }
}