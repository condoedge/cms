<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\GroupPageItemType;
use Illuminate\Database\Eloquent\Model;

class WhatsNewCardItem extends GroupPageItemType
{
    public const ITEM_NAME = 'newsletter.whats-new-card';
    public const ITEM_TITLE = 'newsletter.whats-new-card';
    public const ITEM_DESCRIPTION = 'newsletter.whats-new-card-desc';

    const GROUP_ITEMS_TYPES = [
        ImgItem::class,
        H1Item::class,
        CKItem::class,
    ];

    public function __construct(Model $pageItem)
    {
        parent::__construct($pageItem);

        $this->groupItemsStyles = [
            ImgItem::class => 'height: auto !important; max-height: 300px; overflow:hidden; border-radius: 15px !important; margin: 0 auto;',
            H1Item::class => fn($pageItem, $parent) => $this->h1ItemStyles($pageItem, $parent),
            CKItem::class => fn($pageItem, $parent) => $this->ckItemStyles($pageItem, $parent),
        ];
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows();
    }

    protected function ckItemStyles($pageItem, $parentPageItem)
    {
        $styles = '';

        $styles .= 'font-size: ' . $parentPageItem->getFontSize() . '!important ;';
        $styles .= 'color: ' . $parentPageItem->getTextColor(). '!important ;';

        return $styles;
    }

    protected function h1ItemStyles($pageItem, $parentPageItem)
    {
        $styles = 'font-size: 1.4rem !important; padding: 10px 0;';

        $styles .= 'color: ' . $parentPageItem->getTextColor(). '!important ;';

        return $styles;
    }
}
