<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;
use function Laravel\Prompts\table;

class ButtonItem extends PageItemType
{
    public const ITEM_TAG = 'a';
    public const ITEM_NAME = 'button';
    public const ITEM_TITLE = 'cms::cms.items.button';
    public const ITEM_DESCRIPTION = 'cms::cms.items.button';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'title' => $pageItem->title,
            'href' => $pageItem->content,
        ];
    }

    public function blockTypeEditorElement()
    {
        $buttonTitleEl = _Translatable('newsletter.button-title')->name($this->nameTitle, $this->interactsWithPageItem);
        $buttonHrefEl = _Input('newsletter.button-href')->name($this->nameContent, $this->interactsWithPageItem);

        if($this->valueTitle) $buttonTitleEl = $buttonTitleEl->default(json_decode($this->valueTitle));
        if($this->valueContent) $buttonHrefEl = $buttonHrefEl->default(json_decode($this->valueContent));

        return _Columns(
            $buttonTitleEl,
            $buttonHrefEl,
        );
    }

    protected function toElement($withEditor = null)
    {
        return !$this->content->href || !$this->content->title ? null : _Link($this->content->title)
            ->target('_blank')
            ->href($this->content->href);
    }

    public function toHtml(): string
    {
        $originalStyles = $this->styles;
        $tdStyles = collect($this->styles->getProperties(['color', 'background-color', 'border-color', 'text-decoration', 'width', 'max-width', 'border-radius', 'margin']))->map(function ($value, $key) {
            return $key . ': ' . $value . ';';
        })->implode(' ');
        $originalStyles->removeProperties([ 'margin', 'width']);

        return !$this->content->href || !$this->content->title ? '' : str_replace("\r\n", '', $this->centerElement($this->centerElement(
            '<a target="_blank" href="' . $this->content->href . '" style="' . $originalStyles . 'width: 100% !important;" class="'. $this->classes . '">' . $this->content->title . '</a>'
        , $tdStyles, "30%", tableStyles: 'width:' . '30% !important;'), tableStyles: 'padding: 10px 0 !important; table-layout:fixed;'));
    }

    public function rules()
    {
        return [
            'title' => 'required',
            'content' => 'required',
        ];
    }

    public function defaultClasses($pageItem): string
    {
        return '';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'text-align: center !important; padding: 15px 4px !important; margin: 10px auto !important; color: white !important; display: inline-block; font-weight: 600; width: 30%;border-radius: 5px; min-width: 200px; text-decoration: none;';

        $styles .= 'background: ' . $pageItem->styles?->background_color . '!important;';

        return $styles;
    }

    public function blockTypeEditorStylesElement()
    {
        $colorOptions = collect($this->presetsColors)->mapWithKeys(function($values, $color) {
            return [$color => $values['label'] ?? 'cms::cms.' . $color];
        })->toArray();

        return _Rows(
            _InputNumber('cms::cms.font-size')->name('font-size', false)->default($this->pageItem->getFontSize())->class('mb-2 whiteField'),
            _Select('cms::cms.preset-color')
                ->options($colorOptions)
                ->default($this->styles->preset_color ?? collect($colorOptions)->keys()->first())
                ->name('preset-color', false)
                ->class('whiteField'),
            _Input('cms::cms.link-color')->type('color')->default($this->pageItem->getLinkColor())->name('link-color', false)->class('mb-2 whiteField'),
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0)->class('mb-2 whiteField'),
            _Rows(
                _Html('cms::cms.border-widths')->class('text-sm font-semibold mb-4'),
                $this->borderWidthsStylesEls(),
            )->class('mt-1'),
        );
    }


    public static function getDefaultFontSize($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->font_size_raw ?? 14;
    }
}
