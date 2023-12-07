<?php

namespace Anonimatrix\PageEditor\Cms\ItemTypes;

use Anonimatrix\PageEditor\Cms\PageItemType;
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

    protected function toElement()
    {
        return _Html($this->toHtml());
    }

    public function toHtml(): string
    {
        return '<video style="' . $this->styles .  '"  class="'. $this->classes . '" autoplay="" loop="" muted="" playsinline="" controlslist="nodownload,nofullscreen,noremoteplayback">
            <source src="'.asset($this->content).'" type="video/mp4">
            Your browser does not support the video tag.
        </video>';
    }
}
