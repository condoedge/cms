<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Page;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Query;

class ArticleSearchQuery extends Query
{
    public $class = "bg-white py-8 px-4";
    public $itemsWrapperStyle = "max-width: 800px; margin: 0 auto; width: 100%;";

    protected $search;
    protected $tags;

    public function created()
    {
        $this->search = $this->prop("search") ?? request('search');
        $this->tags = ($this->prop('tags_ids') ? explode(',', $this->prop('tags_ids')) : null) ?? request('tags_ids');
    }

    public function top()
    {
        return _Rows(
            // _Html('cms::wiki.search-results-subtitle')->class('text-3xl text-center mb-6'),
        );
    }

    public function query()
    {
        return Page::where('group_type', 'knowledge')->where('associated_route', '!=', 'knowledge.whats-new')
            ->where(fn($q) => $q->where('title', 'like', '%'.$this->search.'%')
                ->orWhereHas('tags', fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            )->when($this->tags, fn($q) => $q->where(fn($q) => $q->forTags($this->tags)));
    }

    public function render($article)
    {
        return _FlexBetween(
            _Rows(
                _Link($article->title)->class('text-black text-lg')->knowledgeDrawer(ArticlePage::class, ['id' => $article->id]),
                $article->tags->count() > 0 ? _Columns(
                    $article->tags->map(function ($tag) {
                        return _Link($tag->name)->class('bg-info bg-opacity-20 text-blue-500 rounded-lg px-2 py-1 mr-2 max-w-max')->knowledgeDrawer(ArticlePage::class, ['tags_ids' => [$tag->id]]);
                    }),
                )->class('mt-2') : null,
            ),
            auth()->user()?->isAdmin() ? _FlexEnd(
                _Link()->icon('pencil')->class('text-blue-500')->href('knowledge.editor', ['id' => $article->id])->target('_blank'),
            ) : null,
            true ? null : PageEditor::getPagePreviewComponent(),
        )->class('w-full bg-gray-100 px-8 py-4 mb-4 rounded-xl');
    }
}