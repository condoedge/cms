<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Page;
use Kompo\Table;

class ArticleRawList extends Table
{
    public $orderable = 'order';
	public $dragHandle = '.dragHandle';
	public $browseAfterOrder = true;

	public $id = 'pages-orderable-list';

	public function query()
	{
		return Page::whereNull('page_id')->orderBy('order')
            ->where('group_type', 'knowledge');
	}

	public function top()
	{
		return _Rows(
			_Flexbetween(
				_H1('cms::wiki.articles')->medTitle()->class('text-level3'),
				_Link('cms::wiki.create-article')->button()->icon('icon-plus')->href('knowledge.editor'),
			),
            _FlexEnd(
                _Input()->placeholder('cms::wiki..search')->name('title')->class('mb-0 whiteField w-full')->filter()
            ),
		)->class('space-y-4 mb-4');
	}

    public function headers()
    {
        return [
            _Th('#')->class('pl-14'),
            _Th('cms::wiki.title')->class('pl-4'),
            _Th('cms::wiki.actions')->class('pr-2 w-20'),
        ];
    }

    public function render($page)
    {
        return _TableRow(
            _Html(),
            _Link($page->title)->href('knowledge.editor', ['id'=> $page->id]),
            _Link()->icon('pencil')->href('knowledge.editor', ['id'=> $page->id]),
        );
    }
}