<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class ImgItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'img';
    public const ITEM_TITLE = 'newsletter.image';
    public const ITEM_DESCRIPTION = 'newsletter.add-an-image-to-the-block';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'image' => $pageItem->image,
            'title' => $pageItem->title,
        ];
    }

    public function blockTypeEditorElement()
    {
        $item = _Image('newsletter.image')->name($this->nameImage, $this->interactsWithPageItem);

        if ($this->valueImage) $item = $item->default($this->valueImage);

        return _Rows(
            $item,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            $this->sizeStyles(),
            $this->justifyStyles(),
            $this->cornerRadiusStyle(),
        );
    }

    protected function sizeStyles()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-height-px')->name('height', false)->value((int) ($this?->styles->height_raw ?: 100)),
            _InputNumber('newsletter.page-item-width-px')->name('width', false)->value((int) ($this?->styles->width_raw ?: 100)),
        );
    }

    protected function justifyStyles()
    {
        return _Rows(
            _ButtonGroup('newsletter.page-item-justify')->class('mt-4')->name('justify', false)->options([
                'start' => __('cms.left'),
                'center' => __('cms.center'),
                'end' => __('cms.right'),
            ])->optionClass('px-4 py-2 text-center cursor-pointer')
            ->selectedClass('bg-level3 text-white font-medium', 'bg-gray-200 text-level3 font-medium')
            ->value($this->styles->align_items ?: 'center'),
        );
    }

    protected function cornerRadiusStyle()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0),
        );
    }

    public function afterSave($model = null)
    {
        parent::afterSave($model);

        $styleModel = $this->pageItem->getOrCreateStyles();

        PageStyle::setStylesToModel($styleModel);

        $styleModel->save();
    }

    protected function toElement()
    {
        $styles = $this->imgStyles();

        return !$this->content?->image ? null : _Rows(
            _Img()->src(\Storage::url($this->content->image['path']))
                ->style($styles)
        )->class('w-full');
    }

    public function toHtml(): string
    {
        $imageUrl = $this->content?->image;
        if (!$imageUrl) {
            return '';
        }

        $imageUrl = \Storage::disk('public')->url($this->content->image['path']);

        $styles = $this->imgStyles();

        $this->styles->replaceProperty('width', '100% !important');
        $this->styles->replaceProperty('display', null);

        return $this->alignElement(
            "<img src=\"{$imageUrl}\" style=\"{$styles}\" />", 
            $this->styles->getRawProperty('align-items') ?? 'left', 
            $this->styles,
        );
    }

    protected function imgStyles()
    {
        $height = $this->styles->height;
        $width = $this->styles->width;
        $borderRadius = $this->styles->border_radius;
        $minHeight = $this->styles->min_height;
        $backgroundRepeat = $this->styles->background_repeat;
        $backgroundSize = $this->styles->background_size;
        $backgroundPosition = $this->styles->background_position;

        $this->styles->removeProperties(['height', 'width', 'border-radius', 'min-height', 'background-repeat', 'background-size']);

        return "width: {$width};height:{$height};border-radius: {$borderRadius}; min-height: {$minHeight}; background-repeat: {$backgroundRepeat}; background-size: {$backgroundSize}; background-position: {$backgroundPosition};";
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'background-position: center center;';

        return $styles;
    }
}
