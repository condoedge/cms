<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class SpacerItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'spacer';
    public const ITEM_TITLE = 'cms::cms.items.spacer';
    public const ITEM_DESCRIPTION = 'cms::cms.items.spacer-desc';
    public const ITEM_ICON = 'arrow-swap';
    public const ONLY_CUSTOM_STYLES = true;

    public function blockTypeEditorElement()
    {
        return _Rows(
            _InputNumber('cms::cms.spacer-height')->name('height', false)
                ->value((int) ($this->styles->height_raw ?: 40))
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
        $height = $this->styles->height_raw ?: 40;

        return _Html('&nbsp;')->style("height: {$height}px; width: 100%;");
    }

    public function toHtml(): string
    {
        $height = $this->styles->height_raw ?: 40;

        return '<div style="height: ' . $height . 'px; width: 100%; line-height: ' . $height . 'px; font-size: 0; mso-line-height-rule: exactly;">&nbsp;</div>';
    }
}
