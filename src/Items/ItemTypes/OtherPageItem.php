<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;

class OtherPageItem extends PageItemType
{
    public const ITEM_TAG = 'article';
    public const ITEM_NAME = 'article';
    public const ITEM_TITLE = 'newsletter.article';
    public const ITEM_DESCRIPTION = 'newsletter.article';

    public const ONLY_CUSTOM_STYLES = true;

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'page_id' => $pageItem->content,
        ];
    }

    public function blockTypeEditorElement()
    {
        $item = _Select('cms.page')
            ->options(PageModel::when(Features::hasFeature('teams'), 
                fn($q) => $q->where('team_id', auth()->user()->current_team_id))
                    ->where('id', '!=', $this->pageItem->page_id)
                    ->where('group_type', $this->pageItem->page->group_type)
                    ->get()->pluck('title', 'id')
            )
            ->name($this->nameContent, $this->interactsWithPageItem);

        if($this->valueContent) $item = $item->default(json_decode($this->valueContent));

        return $item;
    }

    protected function toElement($withEditor = null)
    {
        return _Rows(
            _Html()->class('w-full border border-gray-200 my-4'),
            _Rows(
                PageModel::findOrFail($this->content->page_id)->getPreview()
            ),
        );
    }

    public function toHtml(): string
    {
        return PageModel::findOrFail($this->content->page_id)->getHtmlContent();
    }

    public function rules()
    {
        return [
            'content' => 'required|exists:pages,id',
        ];
    }
}
