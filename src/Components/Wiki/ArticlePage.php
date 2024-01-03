<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\Knowledge\Models\KnowledgePage;
use Anonimatrix\PageEditor\Models\Tags\Tag;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Illuminate\Support\Facades\Route;
use Kompo\Form;

class ArticlePage extends Form
{
    public $containerClass = "min-h-screen bg-white pb-8";
    public $style = "min-width: 700px;";

    public $model = KnowledgePage::class;

    public function render()
    {
        $routeName = Route::currentRouteName();

        return _Rows(
            $this->searchTop(),
            _Rows(
                _Rows()->class('h-24 bg-white'),
                _Panel(
                    $routeName === 'knowledge.whats-new' ? $this->getWhatsNewContent() : (
                        $this->model?->id ? $this->preview() :
                        new ArticleSearchQuery()
                    ),
                )->id('articles_panel'),
            )->class('bg-white'),
        )->style('background-color: ' . $this->model->getExteriorBackgroundColor());
    }

    protected function searchTop()
    {
        $newsCount = KnowledgePage::whatsNewUnreadedCount();

        return _Rows(
            _Rows(
                _Html('wiki.search-subtitle')->class('text-3xl text-center mb-6'),
                _Input()->icon('search')->name('search', false)->placeholder('wiki.search-placeholder')->class('border border-gray-300 rounded-lg whiteField')
                    ->selfPost('getArticlesContent')->withAllFormValues()->inPanel('articles_panel'),
                _MultiSelect()->icon('tag')
                    ->options(
                        Tag::forPage()->pluck('name','id'),
                    )
                    ->name('tags_ids', false)->placeholder('wiki.tags-placeholder')
                    ->default(request('tags_ids'))
                    ->class('border border-gray-300 rounded-lg whiteField')
                    ->selfPost('getArticlesContent')->withAllFormValues()->inPanel('articles_panel'),
            )->class('max-w-4xl w-full mb-4'),
            _Rows(
                _Columns(
                    $this->mainLink('book-open','wiki.general-help')->href('knowledge.articles'),
                    $this->mainLink('question-mark-circle','wiki.contextual-help')->href('knowledge.faq'),
                    _Rows(
                        (!auth()->user() || !$newsCount) ? null : _Html($newsCount)->class('absolute top-12 right-10 bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center z-20 text-xl font-semibold'),
                        $this->mainLink('light-bulb','wiki.new-features')->href('knowledge.whats-new'),
                    )->class('relative'),
                )->class('absolute max-w-4xl w-full px-8 z-10 left-1/2 transform -translate-x-1/2'),
            )->class('relative h-12 w-full hidden md:flex'),
        )->class('bg-slate-200 p-8 items-center border-b border-gray-300');
    }

    protected function mainLink($icon,$title)
    {
        return _Rows(
            _Svg($icon)->class('w-20 h-20 mx-auto text-gray-700'),
            _Html($title)->class('text-xl text-center'),
        )->class('bg-white rounded-lg px-8 border border-gray-200 z-10 py-4 hover:bg-gray-100 transition-all duration-200');
    }

    protected function preview()
    {
        return _Rows(
            !auth()->user()?->isAdmin() ? null : 
                _Rows(
                    _Link('wiki.edit-article')->href('knowledge.editor', ['id' => $this->model->id]),
                )->class('mb-4 items-center'),
            _Rows(
                _Link('wiki.back-to-all-articles')->icon('arrow-left')->href('knowledge.articles')->class('max-w-max'),
            )->class('px-8 mb-4'),
            PageEditor::getPagePreviewComponent([
                'page_id' => $this->model->id,
            ]),
            new ArticleOpinionForm($this->model->id),
        )->class('py-8 max-w-7xl mx-auto');
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

        if(!request('search') && !request('tags_ids')) {
            return $this->preview();
        }

        return new ArticleSearchQuery();
    }
}