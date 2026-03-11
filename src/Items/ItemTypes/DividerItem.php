<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class DividerItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'divider';
    public const ITEM_TITLE = 'cms::cms.items.divider';
    public const ITEM_DESCRIPTION = 'cms::cms.items.divider-desc';
    public const ITEM_ICON = 'minus';
    public const ONLY_CUSTOM_STYLES = true;

    public function blockTypeEditorElement()
    {
        return _Rows(
            _Select('cms::cms.divider-style')->name('divider-style', false)
                ->options([
                    'solid' => __('cms::cms.divider-solid'),
                    'dashed' => __('cms::cms.divider-dashed'),
                    'dotted' => __('cms::cms.divider-dotted'),
                ])
                ->default($this->styles->divider_style ?: 'solid')
                ->class('whiteField'),
            _InputNumber('cms::cms.divider-thickness')->name('divider-thickness', false)
                ->value((int) ($this->styles->divider_thickness_raw ?: 1))
                ->class('whiteField'),
            _Input('cms::cms.divider-color')->type('color')->name('divider-color', false)
                ->default($this->styles->divider_color ?: '#d5d5d5')
                ->class('whiteField'),
            _InputNumber('cms::cms.divider-width')->name('divider-width', false)
                ->value((int) ($this->styles->divider_width_raw ?: 100))
                ->class('whiteField'),
        );
    }

    public function afterSave($model = null)
    {
        parent::afterSave($model);

        $styleModel = $this->pageItem->getOrCreateStyles();

        PageStyle::setStylesToModel($styleModel);

        $styleModel->save();
    }

    protected function toElement($withEditor = null)
    {
        return _Html('<hr>')->style($this->dividerStyles())->class('w-full');
    }

    public function toHtml(): string
    {
        $style = $this->styles->divider_style ?: 'solid';
        $thickness = $this->styles->divider_thickness_raw ?: 1;
        $color = $this->styles->divider_color ?: '#d5d5d5';
        $width = $this->styles->divider_width_raw ?: 100;

        return '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="' . $this->styles . '">
            <tr>
                <td align="center">
                    <div style="width: ' . $width . '%; border-top: ' . $thickness . 'px ' . $style . ' ' . $color . '; line-height: 0; font-size: 0;">&nbsp;</div>
                </td>
            </tr>
        </table>';
    }

    protected function dividerStyles(): string
    {
        $style = $this->styles->divider_style ?: 'solid';
        $thickness = $this->styles->divider_thickness_raw ?: 1;
        $color = $this->styles->divider_color ?: '#d5d5d5';
        $width = $this->styles->divider_width_raw ?: 100;

        return "display: flex; justify-content: center; align-items: center; width: 100%; padding: 10px 0; border: none; border-top: {$thickness}px {$style} {$color}; max-width: {$width}%; margin: 0 auto;";
    }
}
