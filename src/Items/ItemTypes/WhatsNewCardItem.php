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
            ImgItem::class => fn($pageItem, $parent) => $this->imgItemStyles($pageItem, $parent),
            H1Item::class => fn($pageItem, $parent) => $this->h1ItemStyles($pageItem, $parent),
            CKItem::class => fn($pageItem, $parent) => $this->ckItemStyles($pageItem, $parent),
        ];
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-height-px')->name('img-height', false)->value((int) ($this?->styles->img_height_raw ?: 200))->class('whiteField'),
            _InputNumber('newsletter.page-item-width-px')->name('img-width', false)->value((int) ($this?->styles->img_width_raw ?: null))->class('whiteField'),
            _Panel(
                ImgItem::getDefaultMaxWidth($this->pageItem->getStyleProperty('img_max_width_raw') ?: 80, 'img-max-width'),
            )->id(ImgItem::PANEL_MAX_WIDTH_ID),
            _InputNumber('newsletter.page-item-corner-radius-px')->name('img-border-radius', false)->value((int) $this->styles->img_border_radius_raw ?: 0)->class('whiteField'),
        );
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

    protected function imgItemStyles($pageItem, $parentPageItem)
    {
        $styles = '';

        $styles .= 'height: ' . $parentPageItem->getStyleProperty('img_height') . '!important;';
        $styles .= 'width: ' . $parentPageItem->getStyleProperty('img_width') . '!important;';
        $styles .= 'max-width: ' . $parentPageItem->getStyleProperty('img_max_width') . '!important;';
        $styles .= 'border-radius: ' . $parentPageItem->getStyleProperty('img_border_radius') . '!important;';
        $styles .= 'margin: 0 auto !important;';
        $styles .= 'align-items: center;';

        return $styles;
    }
}
