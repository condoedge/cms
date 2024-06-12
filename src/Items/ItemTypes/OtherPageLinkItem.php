<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Components\Wiki\ArticlePage;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;

class OtherPageLinkItem extends PageItemType
{
    public const ITEM_TAG = 'article_link';
    public const ITEM_NAME = 'article_link';
    public const ITEM_TITLE = 'translate.cms::cms.items.article-link';
    public const ITEM_DESCRIPTION = 'translate.cms::cms.items.article-link';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'page_id' => $pageItem->content,
        ];
    }

    public function blockTypeEditorElement()
    {
        $item = _Select('cms::cms.page')
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
        $page = PageModel::findOrFail($this->content->page_id);

        return _Link($page->title)
            ->knowledgeDrawer(ArticlePage::class, ['id' => $page->id]);
    }

    public function toHtml(): string
    {
        $page = PageModel::findOrFail($this->content->page_id);

        return $this->centerElement(
            '<a target="_blank" href="' . route('page.preview', $page->id) . '" style="' . $this->styles . '" class="'. $this->classes . '">' . $page->title . '</a>'
        );
    }

    public function defaultClasses($pageItem): string
    {
        return '';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'text-align: center !important; padding: 10px 0 !important; margin: 10px auto !important; color: white !important; display: inline-block; font-weight: 600; width: 30%;border-radius: 5px;';

        $styles .= 'background-color: ' . $pageItem->styles?->background_color . '!important;';

        return $styles;
    }

    public function rules()
    {
        return [
            'content' => 'required|exists:pages,id',
        ];
    }
}
