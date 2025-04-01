<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Wiki\KnowledgePage;
use Anonimatrix\PageEditor\Models\Tags\Tag;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Illuminate\Support\Facades\Route;
use Kompo\Form;

class ArticlePage extends Form
{
    public $class = "min-h-screen bg-white pb-8";
    public $style = "width: 700px;";

    public $model = KnowledgePage::class;

    protected $tagsIds = [];

    public function created()
    {
        $this->tagsIds = collect(request('tags_ids'))->map(fn($id) => preg_replace('/\D*/', '', $id))->toArray();

        $this->style .= 'background-color: ' . $this->model->getExteriorBackgroundColor();
    }

    public function render()
    {
        $routeName = request('whats-new') ? 'knowledge.whats-new' : Route::currentRouteName();

        return _Rows(
            $this->searchTop(),
            _Rows(
                _Rows()->class('h-10 bg-wikibg'),
                _Panel(
                    $routeName === 'knowledge.whats-new' ? $this->getWhatsNewContent() : (
                        $this->model?->id ? $this->preview() :
                        new ArticleSearchQuery()
                    ),
                )->id('articles_panel'),
            ),
        );
    }

    protected function searchTop()
    {
        $newsCount = KnowledgePage::whatsNewUnreadedCount();

        return _Rows(
            _Rows(
                _Html('cms::wiki.search-subtitle')->class('text-xl text-center mb-6 text-white'),
                _Columns(
                    _Input()->icon('search')->name('search', false)->class('rounded-lg')
                        ->selfPost('getArticlesContent')->withAllFormValues()->inPanel('articles_panel'),
                    _MultiSelect()->icon('tag')
                        ->options(
                            Tag::forPage()->pluck('name','id'),
                        )
                        ->name('tags_ids', false)
                        ->default($this->tagsIds)
                        ->class('rounded-lg')
                        ->selfPost('getArticlesContent')->withAllFormValues()->inPanel('articles_panel'),
                ),
            )->class('max-w-4xl w-full mb-2'),
            _Rows(
                _Columns(
                    $this->mainLink('book','cms::wiki.general-help')->knowledgeDrawer(ArticlePage::class),
                    $this->mainLink('gps','cms::wiki.contextual-help')->knowledgeDrawer(ArticlePage::class),
                    _Rows(
                        (!auth()->user() || !$newsCount) ? null : _Html($newsCount)->class('absolute top-8 right-10 bg-danger text-white rounded-full w-6 h-6 text-sm flex items-center justify-center z-20 font-semibold'),
                        $this->mainLink('lamp-charge','cms::wiki.new-features')->knowledgeDrawer(ArticlePage::class, ['whats-new' => 1]),
                    )->class('relative'),
                )->class('absolute max-w-4xl w-full px-12 z-10 left-1/2 transform -translate-x-1/2 pr-4'),
            )->class('relative h-4 w-full hidden md:flex items-center'),
        )->class('bg-level1 p-8 items-center');
    }

    protected function mainLink($icon,$title)
    {
        return _Rows(
            _Sax($icon, 36)->class('w-10 h-10 mx-auto text-level1'),
            _Html($title)->class('text-sm text-center mt-2 text-level1'),
        )->class('h-24 justify-center bg-wikibg rounded-xl px-4 border border-level1 z-10 py-4 hover:bg-gray-200 transition-all duration-200');
    }

    protected function preview()
    {
        return _Rows(
            !auth()->user()?->isCmsAdmin() ? null :
                _Rows(
                    _Link('cms::wiki.edit-article')->target('_blank')->href('knowledge.editor', ['id' => $this->model->id]),
                )->class('mb-4 items-center'),
            _Rows(
                _Link('cms::wiki.back-to-all-articles')->icon('arrow-left')->knowledgeDrawer(ArticlePage::class)->class('max-w-max'),
            )->class('px-8 mb-4'),
            PageEditor::getPagePreviewComponent([
                'page_id' => $this->model->id,
            ]),
            new ArticleOpinionForm($this->model->id),
        )->class('bg-wikibg py-8 max-w-7xl mx-auto');
    }

    public function getWhatsNewContent()
    {
        return new WhatsNewQuery();
    }

    public function getArticlesContent()
    {
        if (!$this->model?->id) {
            return new ArticleSearchQuery();
        }

        if(!request('search') && !$this->tagsIds) {
            return $this->preview();
        }

        return new ArticleSearchQuery();
    }
}
