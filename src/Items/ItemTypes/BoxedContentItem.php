<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class BoxedContentItem extends PageItemType
{
    public const ITEM_NAME = 'boxed_content';
    public const ITEM_TITLE = 'newsletter.boxed-content';
    public const ITEM_DESCRIPTION = 'newsletter.boxed-content-desc';

    public const ONLY_CUSTOM_STYLES = true;

    protected $presetsColors;

    public function __construct(\Anonimatrix\PageEditor\Models\PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->setHtmlElementsStyles('a', 'color: ' . $this->pageItem->getLinkColor() . '!important;');

        $this->presetsColors = config('page-editor.boxed_content');
    }

    public function blockTypeEditorElement()
    {
        $item = _CKEditorPageItem('Content')->name($this->nameContent, $this->interactsWithPageItem)
            ->withoutHeight()
            ->prependToolbar(['fontColor', 'fontBackgroundColor']);

        if($this->valueContent) $item = $item->default(json_decode($this->valueContent));

        return $item;
    }

    public function blockTypeEditorStylesElement()
    {
        $colorOptions = collect($this->presetsColors)->mapWithKeys(function($values, $color) {
            return [$color => $values['label'] ?? 'translate.page-editor.' . $color];
        })->toArray();

        return _Rows(
            _InputNumber('translate.page-editor.font-size')->name('font-size', false)->default($this->pageItem->getFontSize())->class('mb-2 whiteField'),
            _Select('translate.page-editor.preset-color')
                ->options($colorOptions)
                ->default($this->styles->preset_color ?? collect($colorOptions)->keys()->first())
                ->name('preset-color', false)
                ->class('whiteField'),
            _Input('translate.page-editor.link-color')->type('color')->default($this->pageItem->getLinkColor())->name('link-color', false)->class('mb-2 whiteField'),
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0)->class('mb-2 whiteField'),
            _Rows(
                _Html('translate.page-editor.border-widths')->class('text-sm font-semibold mb-4'),
                $this->borderWidthsStylesEls(),
            )->class('mt-1'),
        );
    }

    public function afterSave($model = null)
    {
        parent::afterSave($model);

        request()->merge([
            'background-color' => $this->presetsColors[(request('preset-color') ?? 'gray')]['background-color'],
            'border-color' => $this->presetsColors[(request('preset-color') ?? 'gray')]['border-color'],
            'color' => $this->presetsColors[(request('preset-color') ?? 'gray')]['color'],
        ]);
    }


    protected function toElement()
    {
        return _Html($this->content)->replaceCKEditorContent($this->variables)
            ->class('ckEditorContent');
    }

    public function toHtml(): string
    {
        $text = replaceAllMentions($this->content, $this->variables);

        return '<div style="' . $this->styles . '" class="'. $this->classes . ' ckEditor">' . $text . '</div>';
    }

    public function beforeMountInGroup($groupItem)
    {
        $this->setHtmlElementsStyles('a', 'color: ' . $groupItem->getLinkColor() . '!important;');
    }

    public function setHtmlElementsStyles($tag, $styles)
    {
        $this->content = preg_replace_callback('/<'.$tag.'(.*?)>/', function($matches) use ($styles) {
            $element = $matches[0];

            if(strpos($element, 'style="') === false) $element = str_replace('>', ' style="' . $styles . '">', $element);
            else $element = preg_replace('/style="(.*?)"/', 'style="$1;' . $styles . '"', $element);

            return $element;
        }, $this->content);
    }

    public function defaultClasses($pageItem): string
    {
        return '';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'text-align: ' . $pageItem->text_align . ';';

        return $styles;
    }
}