<?php

namespace App\Cms\ItemTypes;

use App\Cms\GroupPageItemType;

class ElementType1Item extends GroupPageItemType
{
    public const ITEM_NAME = 'newsletter.group';
    public const ITEM_TITLE = 'newsletter.element-type-1';
    public const ITEM_DESCRIPTION = 'newsletter.element-type-1-desc';

    const GROUP_ITEMS_TYPES = [
        H2Item::class,
        ImgItem::class,
        CKItem::class,
        ButtonItem::class,
    ];

    public function __construct(\App\Models\Cms\PageItem $pageItem)
    {
        parent::__construct($pageItem);

        $this->groupItemsStyles = [
            H2Item::class => fn($pageItem, $parent) => $this->h2ItemStyles($pageItem, $parent),
            ButtonItem::class => fn($pageItem, $parent) => $this->buttonItemStyles($pageItem, $parent),
            CKItem::class => fn($pageItem, $parent) => $this->ckItemStyles($pageItem, $parent),
            ImgItem::class => 'width: 600px !important; height: auto !important; margin-bottom: 10px !important;',
        ];
    }

    protected function ckItemStyles($pageItem, $parentPageItem)
    {
        $styles = 'padding: 30px !important;';

        $styles .= 'font-size: ' . $parentPageItem->getFontSize() . '!important ;';
        $styles .= 'color: ' . $parentPageItem->getTextColor(). '!important ;';

        return $styles;
    }

    protected function h2ItemStyles($pageItem, $parentPageItem)
    {
        $styles = 'font-size: 1.4rem !important; padding: 10px 0; text-align: center;';

        $styles .= 'color: ' . $parentPageItem->getTitleColor(). '!important ;';

        return $styles;
    }

    protected function buttonItemStyles($pageItem, $parentPageItem)
    {
        $styles = 'border-radius: 5px;';
        $styles .= 'background-color: ' . $parentPageItem->getButtonColor() . '!important;';
        $styles .= 'color: white !important;';

        return $styles;
    }
}
