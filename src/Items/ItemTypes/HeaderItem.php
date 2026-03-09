<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class HeaderItem extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'header';
    public const ITEM_TITLE = 'cms::cms.items.image-header';
    public const ITEM_DESCRIPTION = 'cms::cms.items.full-screen-top-of-page-image';

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
        $imgEl = _Image('newsletter.image')
            ->name($this->nameImage, $this->interactsWithPageItem);

        $inputEl = _Translatable('cms::cms.title-optional')
            ->name($this->nameTitle, $this->interactsWithPageItem);

        if($this->valueTitle) $inputEl = $inputEl->default(json_decode($this->valueTitle));
        if($this->valueImage) $imgEl = $imgEl->default($this->valueImage);

       return _Rows(
            $imgEl,
            $inputEl,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _InputNumber('cms::cms.header-height')->name('height', false)
                ->value((int) ($this->styles->height_raw ?: 300))
                ->class('whiteField'),
            _Input('cms::cms.header-text-color')->type('color')->name('header-text-color', false)
                ->default($this->styles->header_text_color ?: '#ffffff')
                ->class('whiteField'),
            _Toggle('cms::cms.header-overlay')->name('header-overlay', false)
                ->value((bool) ($this->styles->header_overlay_raw ?: false))
                ->class('whiteField'),
            _Input('cms::cms.header-overlay-color')->type('color')->name('header-overlay-color', false)
                ->default($this->styles->header_overlay_color ?: '#000000')
                ->class('whiteField'),
            _InputNumber('cms::cms.header-overlay-opacity')->name('header-overlay-opacity', false)
                ->value((int) ($this->styles->header_overlay_opacity_raw ?: 40))
                ->class('whiteField'),
            _Select('cms::cms.header-text-position')->name('header-text-position', false)
                ->options([
                    'flex-start' => __('cms::cms.padding-top'),
                    'center' => __('cms::cms.center'),
                    'flex-end' => __('cms::cms.padding-bottom'),
                ])
                ->default($this->styles->header_text_position ?: 'center')
                ->class('whiteField'),
        );
    }

    public function beforeSave($model = null)
    {
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
        $height = $this->styles->height_raw ?: 300;
        $textColor = $this->styles->header_text_color ?: '#ffffff';
        $textPosition = $this->styles->header_text_position ?: 'center';
        $overlayStyle = $this->buildOverlayStyle();

        $bgStyle = !$this->content->image ? '' : "background-image: url('" . \Storage::url($this->content->image['path']) . "');";

        return _Rows(
            _Html('
                <div style="position: relative; width: 100%; height: ' . $height . 'px; ' . $bgStyle . ' background-size: cover; background-position: center; display: flex; align-items: ' . $textPosition . '; justify-content: center;">
                    ' . $overlayStyle . '
                    <div style="position: relative; z-index: 1; color: ' . $textColor . '; text-align: center; font-size: 1.5rem; padding: 20px;">' . ($this->content->title ?: '') . '</div>
                </div>
            '),
        );
    }

    public function toHtml(): string
    {
        $imageUrl = $this->content?->image;
        if (!$imageUrl) {
            return '';
        }

        $imageUrl = \Storage::disk('public')->url($this->content->image['path']);
        $height = $this->styles->height_raw ?: 300;
        $textColor = $this->styles->header_text_color ?: '#ffffff';
        $textPosition = $this->styles->header_text_position ?: 'center';
        $overlayHtml = $this->buildOverlayStyle();

        return '<div class="' . $this->classes . '" style="' . $this->styles . ' position: relative; width: 100%; height: ' . $height . 'px; background-image: url(\'' . $imageUrl . '\'); background-size: cover; background-position: center; display: flex; align-items: ' . $textPosition . '; justify-content: center;">
            ' . $overlayHtml . '
            <div style="position: relative; z-index: 1; color: ' . $textColor . '; text-align: center; font-size: 1.5rem; padding: 20px;">' . ($this->content->title ?: '') . '</div>
        </div>';
    }

    protected function buildOverlayStyle(): string
    {
        $hasOverlay = (bool) ($this->styles->header_overlay_raw ?: false);

        if (!$hasOverlay) {
            return '';
        }

        $overlayColor = $this->styles->header_overlay_color ?: '#000000';
        $overlayOpacity = ((int) ($this->styles->header_overlay_opacity_raw ?: 40)) / 100;

        return '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: ' . $overlayColor . '; opacity: ' . $overlayOpacity . ';"></div>';
    }

    public function rules()
    {
        return [
            'image' => 'required',
        ];
    }
}
