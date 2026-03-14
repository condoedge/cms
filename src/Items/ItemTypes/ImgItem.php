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
    public const ITEM_ICON = 'gallery';

    public const ASPECT_RATIO_FREE = 'free';
    public const ASPECT_RATIO_ORIGINAL = 'original';
    public const ASPECT_RATIO_16_9 = '16:9';
    public const ASPECT_RATIO_4_3 = '4:3';
    public const ASPECT_RATIO_1_1 = '1:1';
    public const ASPECT_RATIO_3_4 = '3:4';
    public const ASPECT_RATIO_9_16 = '9:16';

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

        $altEl = _Translatable('cms::cms.alt-text')->name($this->nameTitle, $this->interactsWithPageItem)
            ->class('mt-2');

        if ($this->valueTitle) $altEl = $altEl->default(json_decode($this->valueTitle));

        $linkEl = _Input('cms::cms.image-link-url')->name($this->nameContent, $this->interactsWithPageItem)
            ->type('url')
            ->placeholder('https://')
            ->class('mt-2');

        if ($this->valueContent) $linkEl = $linkEl->default($this->valueContent);

        return _Rows(
            $item,
            $altEl,
            $linkEl,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            $this->sizeStyles(),
            $this->objectFitStyle(),
            $this->aspectRatioStyle(),
            $this->justifyStylesEls(),
            $this->cornerRadiusStyle(),
        );
    }

    protected function sizeStyles()
    {
        $maxWidth = (int) ($this->pageItem->getStyleProperty('max_width_raw') ?: 100);

        return _Rows(
            _Html('cms::cms.image-width')->class('vlStyleSubLabel'),
            _Html('
                <div class="flex items-center gap-3">
                    <input type="range" name="' . $this->formPrefix . 'max-width" min="10" max="100" step="5" value="' . $maxWidth . '" class="flex-1" oninput="this.nextElementSibling.textContent = this.value + \'%\'" />
                    <span class="text-sm font-semibold text-gray-700 w-10 text-right">' . $maxWidth . '%</span>
                </div>
            '),
            _Hidden()->name($this->formPrefix . 'height-auto', false)->value(1),
        );
    }

    protected function objectFitStyle()
    {
        return _Select('cms::cms.object-fit')->name($this->formPrefix . 'object-fit', false)
            ->options(static::getObjectFitOptions())
            ->default($this->styles->object_fit ?: 'cover')
            ->class('whiteField');
    }

    protected function aspectRatioStyle()
    {
        $currentRatio = $this->styles->aspect_ratio ?: static::ASPECT_RATIO_FREE;

        return _Select('cms::cms.aspect-ratio')->name($this->formPrefix . 'aspect-ratio', false)
            ->options(static::getAspectRatioOptions())
            ->default($currentRatio)
            ->class('whiteField');
    }

    public static function getObjectFitOptions(): array
    {
        return [
            'cover' => __('cms::cms.object-fit-cover'),
            'contain' => __('cms::cms.object-fit-contain'),
        ];
    }

    public static function getAspectRatioOptions(): array
    {
        return [
            static::ASPECT_RATIO_FREE => __('cms::cms.aspect-ratio-free'),
            static::ASPECT_RATIO_ORIGINAL => __('cms::cms.aspect-ratio-original'),
            static::ASPECT_RATIO_16_9 => '16:9',
            static::ASPECT_RATIO_4_3 => '4:3',
            static::ASPECT_RATIO_1_1 => '1:1',
            static::ASPECT_RATIO_3_4 => '3:4',
            static::ASPECT_RATIO_9_16 => '9:16',
        ];
    }

    public static function getDefaultMaxWidth($default = null, $nameProperty = 'max-width')
    {
        $maxWidth = $default ?? request('default') ?: 100;

        $image = request()->file('image');

        if($image) {
            $sizes = getimagesize($image->getRealPath());

            $isPortrait = $sizes[0] < $sizes[1];

            $maxWidth = (int) ($isPortrait ? 80 : 100);
        }

        $maxWidth = $default && !$image ? $default : $maxWidth;

        return _InputNumber('cms::newsletter.page-item-max-width-percent')->name($nameProperty, false)->value((int) ($maxWidth));
    }

    protected function cornerRadiusStyle()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-corner-radius-px')->name($this->formPrefix . 'border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0),
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

        if ($this->interactsWithPageItem) {
            PageStyle::setStylesToModel($styleModel);
        } else {
            $this->saveImageStylesToModel($styleModel);
        }

        $styleModel->save();
    }

    protected function saveImageStylesToModel($styleModel)
    {
        $imageStyles = ['object-fit', 'aspect-ratio', 'max-width', 'border-radius', 'align-items'];

        foreach ($imageStyles as $style) {
            $value = request($this->formPrefix . $style);

            if (!is_null($value)) {
                $suffix = config("page-editor.automapping_styles.$style", '');
                $styleModel->content->replaceProperty($style, $value . $suffix);
            }
        }
    }

    protected function toElement($withEditor = null)
    {
        $styles = $this->imgStyles();

        $el = !$this->content?->image ? null : _Rows(
            _Img()->src(\Storage::url($this->content->image_preview['path']))
                ->style($styles)
                ->attr(['alt' => $this->content->title ?: ''])
        )->class('w-full');

        if(!$withEditor && $el) {
            $linkUrl = $this->pageItem->content;
            if ($linkUrl && filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                $el = $el->attr(['title' => $linkUrl]);
            } else {
                $el = $el->onClick(fn($e) => $e->get('page-editor.get-full-view', ['path' => $this->content->image['path']])->inModal());
            }
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
        $altText = htmlspecialchars($this->content->title ?: '', ENT_QUOTES);

        $styles = $this->imgStylesForEmail();
        $align = $this->styles->getRawProperty('align-items') ?? 'center';

        $this->styles->removeProperties(['height', 'width', 'max-width', 'min-height', 'background-repeat', 'background-size', 'border-radius', 'object-fit', 'aspect-ratio']);
        $this->styles->replaceProperty('width', '100% !important');
        $this->styles->replaceProperty('display', null);

        $widthAttr = $this->styles->width_raw ? (int) $this->styles->width_raw : '100%';
        $heightAttr = ($this->styles->height_auto_raw ?? true) ? 'auto' : (int) $this->styles->height_raw;

        $imgTag = "<img src=\"{$imageUrl}\" alt=\"{$altText}\" style=\"{$styles} display:block; border:0; outline:none; text-decoration:none;\" width=\"{$widthAttr}\" height=\"{$heightAttr}\" />";

        $linkUrl = $this->pageItem->content;
        if ($linkUrl && filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            $linkUrl = htmlspecialchars($linkUrl, ENT_QUOTES);
            $imgTag = "<a href=\"{$linkUrl}\" target=\"_blank\" style=\"text-decoration:none; outline:none; border:none;\">{$imgTag}</a>";
        }

        return $this->alignElement(
            $imgTag,
            $align,
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
        $height = ($this->styles->height_auto_raw ?? true) ? 'auto' : $this->styles->height;
        $width = $this->styles->width_raw ? $this->styles->width : '100%';
        $borderRadius = $this->styles->border_radius;
        $minHeight = $this->styles->min_height;
        $maxWidth = $this->styles->max_width;
        $backgroundRepeat = $this->styles->background_repeat;
        $backgroundSize = $this->styles->background_size;
        $backgroundPosition = $this->styles->background_position;
        $objectFit = $this->styles->object_fit ?: 'cover';
        $aspectRatio = $this->resolveAspectRatio();

        $this->styles->removeProperties(['height', 'width', 'max-width', 'min-height', 'background-repeat', 'background-size', 'border-radius', 'object-fit', 'aspect-ratio']);

        $aspectRatioStyle = $aspectRatio ? "aspect-ratio: {$aspectRatio};" : '';

        return "width: {$width};height:{$height};border-radius: {$borderRadius}; min-height: {$minHeight}; max-width: {$maxWidth}; background-repeat: {$backgroundRepeat}; background-size: {$backgroundSize}; background-position: {$backgroundPosition}; object-fit: {$objectFit}; {$aspectRatioStyle}";
    }

    /**
     * Email-safe image styles (no object-fit, aspect-ratio, background-* properties).
     */
    protected function imgStylesForEmail(): string
    {
        $height = ($this->styles->height_auto_raw ?? true) ? 'auto' : $this->styles->height;
        $width = $this->styles->width_raw ? $this->styles->width : '100%';
        $borderRadius = $this->styles->border_radius;
        $maxWidth = $this->styles->max_width;

        return "width: {$width}; height: {$height}; max-width: {$maxWidth}; border-radius: {$borderRadius}; display: block;";
    }

    /**
     * Resolve the CSS aspect-ratio value from the stored style property.
     */
    protected function resolveAspectRatio(): string
    {
        $ratio = $this->styles->aspect_ratio;

        if (!$ratio || $ratio === static::ASPECT_RATIO_FREE) {
            return '';
        }

        if ($ratio === static::ASPECT_RATIO_ORIGINAL) {
            return $this->getOriginalAspectRatio();
        }

        // Convert "16:9" format to "16/9" CSS format
        return str_replace(':', '/', $ratio);
    }

    /**
     * Get the original aspect ratio from the stored image dimensions.
     */
    protected function getOriginalAspectRatio(): string
    {
        $image = $this->content->image ?? $this->content->image_preview ?? null;

        if (!$image || !isset($image['width'], $image['height']) || !$image['height']) {
            return '';
        }

        return $image['width'] . '/' . $image['height'];
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'background-position: center center; padding: 0 !important;';

        return $styles;
    }
}
