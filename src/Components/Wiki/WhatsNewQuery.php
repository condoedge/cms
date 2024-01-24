<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Wiki\KnowledgeView;
use Anonimatrix\PageEditor\Models\Page;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Query;

class WhatsNewQuery extends Query
{
    public $class = "bg-white py-8 px-4";
    public $itemsWrapperStyle = "max-width: 800px; margin: 0 auto; width: 100%;";

    public function top()
    {
        return _Rows(
            _Html('cms::wiki.whats-new-title')->class('text-3xl text-center mb-6'),
        );
    }

    public function query()
    {
        return Page::where('group_type', 'knowledge')->where('associated_route', 'knowledge.whats-new')
            ->orderBy('created_at', 'desc');
    }

    public function render($article)
    {
        return _Rows(
            _FlexEnd(
                _Html($article->created_at->format('d M Y'))->class('text-sm text-gray-500'),
            )->class('mb-2'),
            _Rows(
                _Panel(
                    $this->readLessArticle($article->id),
                )->id('preview_panel' . $article->id),
                !auth()->user()?->isAdmin() ? null : _FlexEnd(
                    _Link('cms::wiki.edit-article')->icon('pencil')->class('text-blue-500')->href('knowledge.editor', ['id' => $article->id])->target('_blank'),
                ),
            )->class('w-full bg-gray-50 px-8 py-4 mb-4 rounded-xl'),
        )->class('mb-4');
    }

    public function readFullArticle($pageId = null)
    {
        $page = Page::findOrFail($pageId);

        $viewData = ['page_id' => $page->id, 'user_id' => auth()->user()?->id, 'ip' => request()->ip()];

        KnowledgeView::updateOrCreate(
            $viewData,
            $viewData,
        );

        return _Rows(
            PageEditor::getPagePreviewComponent([
                'page_id' => $page->id,
            ]),
            _FlexEnd(
                _Link('cms::wiki.read-less')->class('mt-4')->selfGet('readLessArticle', ['id' => $page->id])->inPanel('preview_panel' . $page->id),
            ),
        );
    }

    public function readLessArticle($pageId = null)
    {
        $page = Page::findOrFail($pageId);

        return _Rows(
            $page->pageItems()->orderBy('order')->first()?->getPageItemType()->toPreviewElement(),
            _FlexEnd(
                _Link('cms::wiki.read-more')->class('mt-4')->selfGet('readFullArticle', ['id' => $page->id])->inPanel('preview_panel' . $page->id),
            ),
        )->class('w-full bg-gray-50 px-8 py-4 mb-4 rounded-xl');
    }
}