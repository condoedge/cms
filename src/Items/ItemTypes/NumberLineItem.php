<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;

class NumberLineItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'number_line';
    public const ITEM_TITLE = 'cms::cms.items.number-line';
    public const ITEM_DESCRIPTION = 'cms::cms.items.number-line-description';
    public const ITEM_ICON = 'hashtag';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'number' => $pageItem->title,
            'content' => $pageItem->content,
        ];
    }

    public function blockTypeEditorElement()
    {
        $numberEl = _InputNumber('cms::newsletter.number')
            ->name($this->nameTitle, $this->interactsWithPageItem);

        $contentEl = _Translatable('cms::cms.content')
            ->name($this->nameContent, $this->interactsWithPageItem);

        if($this->valueTitle) $numberEl = $numberEl->default(json_decode($this->valueTitle));
        if($this->valueContent) $contentEl = $contentEl->default($this->valueContent);

       return _Rows(
            $numberEl,
            $contentEl,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _Input('cms::cms.bg-number-color')->type('color')->default($this->pageItem->getStyleProperty('bg_number_color') ?: '#000000')->name('bg-number-color', false)->class('mb-2 whiteField'),
            _InputNumber('cms::cms.font-size-number')->min(0)->default($this->pageItem->getStyleProperty('font_size_number_raw') ?: 18)->name('font-size-number', false)->class('mb-2 whiteField'),
            _InputNumber('cms::cms.bg-size-number')->min(0)->default($this->pageItem->getStyleProperty('bg_size_number_raw') ?: 32)->name('bg-size-number', false)->class('mb-2 whiteField'),
        );
    }

    protected function toElement($withEditor = null)
    {
        $numberElStyles = 'background-color: ' . $this->pageItem->getStyleProperty('bg_number_color') . ';' . 'font-size: ' . $this->pageItem->getStyleProperty('font_size_number') . ';' .
            'width:' . $this->pageItem->getStyleProperty('bg_size_number') . ';' . 'height:' . $this->pageItem->getStyleProperty('bg_size_number') . ';';

        return _Flex(
            _Html($this->content->number)->class('rounded-full text-white flex items-center justify-center text-lg')
                ->style($numberElStyles),
            _Html($this->content->content),
        )->class('gap-4 items-center');
    }

    public function toHtml(): string
    {
        // Table-based layout (email clients don't support flexbox). The number
        // badge + the content text sit in two cells, mirroring toElement().
        $bgColor = $this->pageItem->getStyleProperty('bg_number_color') ?: '#000000';
        $fontSize = $this->pageItem->getStyleProperty('font_size_number') ?: '18px';
        $size = $this->pageItem->getStyleProperty('bg_size_number') ?: '32px';
        $number = e($this->content->number);
        $text = $this->content->content;

        $badge = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
            . '<tr><td align="center" valign="middle" width="' . $size . '" height="' . $size . '" '
            . 'style="width:' . $size . ';height:' . $size . ';background-color:' . $bgColor . ';border-radius:50%;'
            . 'color:#ffffff;text-align:center;font-size:' . $fontSize . ';line-height:' . $size . ';mso-line-height-rule:exactly;">'
            . $number . '</td></tr></table>';

        return $this->openCloseTag(
            '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
            . '<tr>'
            . '<td valign="middle" width="' . $size . '" style="padding-right:16px;">' . $badge . '</td>'
            . '<td valign="middle">' . $text . '</td>'
            . '</tr>'
            . '</table>'
        );
    }

    public function rules()
    {
        return [
            'title' => 'required',
            'content' => 'required',
        ];
    }
}
