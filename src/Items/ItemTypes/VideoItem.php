<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;

class VideoItem extends PageItemType
{
    public const ITEM_TAG = 'video';
    public const ITEM_NAME = 'video';
    public const ITEM_TITLE = 'newsletter.video';
    public const ITEM_DESCRIPTION = 'newsletter.full-screen-top-of-page-video';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = $pageItem->title ?: '';
    }

    public function blockTypeEditorElement()
    {
        $item = _Translatable('newsletter.video-url')
            ->name($this->nameTitle, $this->interactsWithPageItem);

        if($this->valueTitle) $item = $item->default($this->valueTitle);
        
        return $item;
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _InputNumber('newsletter.page-item-max-width-percent')->name('max-width', false)->value((int) $this->styles->max_width_raw ?: 80)->class('whiteField'),
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0)->class('whiteField'),
            $this->justifyStylesEls(),
        );
    }

    protected function videoStyles()
    {
        $borderRadius = $this->styles->border_radius;
        $maxWidth = $this->styles->max_width;

        $this->styles->removeProperties(['border-radius', 'max-width']);

        return "border-radius: {$borderRadius}; max-width: {$maxWidth};";
    }

    protected function toElement()
    {
        return _Html($this->toHtml());
    }

    public function toHtml(): string
    {
        return '<video style="' . $this->videoStyles() .  '"  class="'. $this->classes . '" autoplay="" loop="" muted="" playsinline="" controlslist="nodownload,nofullscreen,noremoteplayback">
            <source src="'.asset($this->content).'" type="video/mp4">
            Your browser does not support the video tag.
        </video>';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);

        $styles .= 'display:flex; flex-direction: column;';

        return $styles;
    }
}
