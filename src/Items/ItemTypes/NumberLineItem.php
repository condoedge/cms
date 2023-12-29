<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;

class NumberLineItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'number_line';
    public const ITEM_TITLE = 'newsletter.number-line';
    public const ITEM_DESCRIPTION = 'newsletter.number-line-description';

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
        $numberEl = _InputNumber('newsletter.number')
            ->name($this->nameTitle, $this->interactsWithPageItem);

        $contentEl = _Translatable('cms.content')
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
            _Input('cms.bg-number-color')->type('color')->default($this->pageItem->getStyleProperty('bg_number_color') ?: '#000000')->name('bg-number-color', false)->class('mb-2 whiteField'),
            _InputNumber('cms.font-size-number')->min(0)->default($this->pageItem->getStyleProperty('font_size_number_raw') ?: 18)->name('font-size-number', false)->class('mb-2 whiteField'),
            _InputNumber('cms.bg-size-number')->min(0)->default($this->pageItem->getStyleProperty('bg_size_number_raw') ?: 32)->name('bg-size-number', false)->class('mb-2 whiteField'),
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
        return $this->openCloseTag("
            <div style=\"display: flex; justify-content:center;\">
                <div style=\"color: white; text-align: center; font-size: 1.5rem;\">{$this->content->title}</div>
            </div>
        ");
    }

    public function rules()
    {
        return [
            'title' => 'required',
            'content' => 'required',
        ];
    }
}
