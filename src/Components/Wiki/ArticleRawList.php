<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Page;
use Kompo\Table;

class ArticleRawList extends Table
{
    // public $orderable = 'order';
	// public $dragHandle = '.dragHandle';
	// public $browseAfterOrder = true;

	public $id = 'pages-orderable-list';

	public function query()
	{
		return Page::whereNull('page_id')
            ->where('group_type', 'knowledge');
	}

	public function top()
	{
		return _Rows(
			_Flexbetween(
				_H1('cms::wiki.articles')->class('text-level3 articleRawListTitle'),
				_Link('cms::wiki.create-article')->button()->icon('icon-plus')->href('knowledge.editor'),
			),
            _FlexEnd(
                _Input()->placeholder('cms::wiki.search')->name('title')->class('mb-0 whiteField w-full')->filter()
            ),
		)->class('space-y-4 mb-4');
	}

    public function headers()
    {
        return [
            _Th('#')->class('pl-14'),
            _Th('cms::wiki.title')->class('pl-4'),
            _Th('cms::wiki.categories'),
            _Th('cms::wiki.actions')->class('pr-2 w-20'),
        ];
    }

    public function render($page)
    {
        return _TableRow(
            _Html(),
            _Link($page->title)->href('knowledge.editor', ['id'=> $page->id]),
            _Rows(
                $page->tags->map(fn($t) => _Html($t->name)->class('text-sm bg-info bg-opacity-20 text-blue-500 rounded-lg px-2 py-1 max-w-max')),
            )->class('flex-wrap gap-2'),
            _Flex4(
                _Link()->icon('pencil')->href('knowledge.editor', ['id'=> $page->id]),
                _DeleteLink()->class('text-red-400')->byKey($page)->refresh(),
                _Toggle()->class('!mb-0')->name('is_visible')->default($page->is_visible)->class('!mb-0')
                    ->selfPost('changePageVisibility', ['id' => $page->id]),
            ),
        );
    }

    public function changePageVisibility($id)
    {
        $page = Page::findOrFail($id);
        $page->is_visible = request('is_visible');
        $page->save();
    }
}