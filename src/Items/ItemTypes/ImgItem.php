<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class ImgItem extends PageItemType
{
    public const PANEL_MAX_WIDTH_ID = 'panelMaxWidth';

    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'img';
    public const ITEM_TITLE = 'cms::cms.items.image';
    public const ITEM_DESCRIPTION = 'cms::cms.items.add-an-image-to-the-block';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'image' => $pageItem->image ?? $pageItem->image_preview,
            'image_preview' => $pageItem->image_preview ?? $pageItem->image,
            'title' => $pageItem->title,
        ];
    }

    public function blockTypeEditorElement()
    {
        $item = _Image('newsletter.image')->name($this->nameImage, false)
            ->default($this->pageItem->image)
            ->id('newsletter-image')
            ->pasteListener('newsletter-image')
            ->post('page-editor.get-image-size', ['default' => $this->pageItem->getStyleProperty('max_width_raw')])->inPanel(static::PANEL_MAX_WIDTH_ID);

        if ($this->valueImage) $item = $item->default($this->valueImage);

        return _Rows(
            $item,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            $this->sizeStyles(),
            $this->justifyStylesEls(),
            $this->cornerRadiusStyle(),
        );
    }

    protected function sizeStyles()
    {
        return _Rows(
            _Toggle('cms::newsletter.page-item-height-auto')->name('height-auto', false)->value((bool) ($this?->styles->height_auto_raw ?: false))->class('whiteField')
                ->toggleId('item-height-px-input', $this?->styles->height_auto_raw),
                
            _InputNumber('cms::newsletter.page-item-height-px')->name('height', false)->value((int) ($this?->styles->height_raw ?: 200))->class('whiteField')->id('item-height-px-input'),
            
            _InputNumber('cms::newsletter.page-item-width-px')->name('width', false)->value((int) ($this?->styles->width_raw ?: null))->class('whiteField'),
            _Panel(
                static::getDefaultMaxWidth($this->pageItem->getStyleProperty('max_width_raw') ?: 80),
            )->id(static::PANEL_MAX_WIDTH_ID),
        );
    }

    public static function getDefaultMaxWidth($default = null, $nameProperty = 'max-width')
    {
        $maxWidth = $default ?? request('default') ?: 100;

        $image = request()->file('image');

        if($image) {
            $sizes = getimagesize($image->getRealPath());

            $isPortrait = $sizes[0] < $sizes[1];

            $maxWidth = (int) ($isPortrait ? 60 : 80);
        }

        // TODO CHECK IF we remove the function beacuse for now we'll override it
        $maxWidth = $default && !$image ? $default : $maxWidth;

        return _InputNumber('cms::newsletter.page-item-max-width-percent')->name($nameProperty, false)->value((int) ($maxWidth))->class('whiteField');
    }

    protected function cornerRadiusStyle()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0)->class('whiteField'),
        );
    }

    public function beforeSave($model = null)
    {      
        $model->manualUploadImage(request()->file('image'), 'image_preview', 800);
        $model->manualUploadImage(request()->file('image'), 'image', 1600);
  
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
        $styles = $this->imgStyles();

        $el = !$this->content?->image ? null : _Rows(
            _Img()->src(\Storage::url($this->content->image_preview['path']))
                ->style($styles)
        )->class('w-full');

        if(!$withEditor && $el) {
            $el = $el->onClick(fn($e) => $e->get('page-editor.get-full-view', ['path' => $this->content->image['path']])->inModal());
        }

        return $el;
    }

    public static function getFullView()
    {
        return _Rows(
            _Img()->src(\Storage::url(request('path'))),
        )->class('w-full overflow-y-auto mini-scroll')->style('max-height: 95vh');
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
            $this->styles->getRawProperty('align-items') ?? 'center', 
            $this->styles,
        );
    }

    public function rules()
    {
        return [
            'image' => 'required',
        ];
    }

    protected function imgStyles()
    {
        $height = $this->styles->height_auto_raw ? 'auto' : $this->styles->height;
        $width = $this->styles->width_raw ? $this->styles->width : '100%';
        $borderRadius = $this->styles->border_radius;
        $minHeight = $this->styles->min_height;
        $maxWidth = $this->styles->max_width;
        $backgroundRepeat = $this->styles->background_repeat;
        $backgroundSize = $this->styles->background_size;
        $backgroundPosition = $this->styles->background_position;

        $this->styles->removeProperties(['height', 'width', 'max-width', 'min-height', 'background-repeat', 'background-size', 'border-radius']);

        return "width: {$width};height:{$height};border-radius: {$borderRadius}; min-height: {$minHeight}; max-width: {$maxWidth}; background-repeat: {$backgroundRepeat}; background-size: {$backgroundSize}; background-position: {$backgroundPosition};";
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'background-position: center center;';

        return $styles;
    }
}
