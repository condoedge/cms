<?php

namespace Anonimatrix\PageEditor\Http\Controllers;

use Anonimatrix\PageEditor\Services\EmailHtmlBuilderService;
use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Illuminate\Routing\Controller as BaseController;

class PageBlocksController extends BaseController
{
    public function __construct(
        protected PageBlockService $blockService,
        protected EmailHtmlBuilderService $htmlBuilder,
    ) {
    }

    public function exportHtml($pageId)
    {
        $page = PageModel::findOrFail($pageId);

        $html = $this->htmlBuilder->buildFromPage($page);

        $filename = str_replace(' ', '-', strtolower($page->title ?: 'email')) . '.html';

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
