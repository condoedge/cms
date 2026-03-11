<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class VideoItem extends PageItemType
{
    public const ITEM_TAG = 'video';
    public const ITEM_NAME = 'video';
    public const ITEM_TITLE = 'cms::cms.items.video';
    public const ITEM_DESCRIPTION = 'cms::cms.items.full-screen-top-of-page-video';
    public const ITEM_ICON = 'video-play';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = $pageItem->title ?: '';
    }

    public function blockTypeEditorElement()
    {
        $item = _Translatable('cms::cms.video-url')
            ->name($this->nameTitle, $this->interactsWithPageItem);

        if($this->valueTitle) $item = $item->default($this->valueTitle);

        return _Rows(
            $item,
            _Html('cms::cms.video-url-help')->class('text-xs text-gray-400 mt-1'),
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _InputNumber('cms::newsletter.page-item-max-width-percent')->name('max-width', false)->value((int) $this->styles->max_width_raw ?: 80)->class('whiteField'),
            _InputNumber('newsletter.page-item-corner-radius-px')->name('border-radius', false)->value((int) $this->styles->border_radius_raw ?: 0)->class('whiteField'),
            $this->justifyStylesEls(),
        );
    }

    public function afterSave($model = null)
    {
        parent::afterSave($model);

        $styleModel = $this->pageItem->getOrCreateStyles();

        PageStyle::setStylesToModel($styleModel);

        $styleModel->save();
    }

    protected function videoStyles()
    {
        $borderRadius = $this->styles->border_radius;
        $maxWidth = $this->styles->max_width;

        $this->styles->removeProperties(['border-radius', 'max-width']);

        return "border-radius: {$borderRadius}; max-width: {$maxWidth};";
    }

    /**
     * Detect the video source type from URL.
     */
    protected function detectVideoType(): string
    {
        $url = $this->content;

        if (preg_match('/youtube\.com|youtu\.be/i', $url)) {
            return 'youtube';
        }

        if (preg_match('/vimeo\.com/i', $url)) {
            return 'vimeo';
        }

        return 'file';
    }

    /**
     * Extract the embed ID from a YouTube or Vimeo URL.
     */
    protected function extractEmbedId(): ?string
    {
        $url = $this->content;

        // YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function toElement($withEditor = null)
    {
        return _Html($this->toHtml());
    }

    public function toHtml(): string
    {
        $type = $this->detectVideoType();

        if ($type === 'youtube') {
            return $this->renderYoutubeEmbed();
        }

        if ($type === 'vimeo') {
            return $this->renderVimeoEmbed();
        }

        return $this->renderVideoFile();
    }

    protected function renderYoutubeEmbed(): string
    {
        $embedId = $this->extractEmbedId();
        if (!$embedId) return '';

        $videoStyles = $this->videoStyles();

        return '<div style="' . $this->styles . ' display:flex; flex-direction: column; align-items: center;">
            <iframe src="https://www.youtube.com/embed/' . $embedId . '" style="width: 100%; aspect-ratio: 16/9; ' . $videoStyles . '" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        </div>';
    }

    protected function renderVimeoEmbed(): string
    {
        $embedId = $this->extractEmbedId();
        if (!$embedId) return '';

        $videoStyles = $this->videoStyles();

        return '<div style="' . $this->styles . ' display:flex; flex-direction: column; align-items: center;">
            <iframe src="https://player.vimeo.com/video/' . $embedId . '" style="width: 100%; aspect-ratio: 16/9; ' . $videoStyles . '" frameborder="0" allowfullscreen allow="autoplay; fullscreen; picture-in-picture"></iframe>
        </div>';
    }

    protected function renderVideoFile(): string
    {
        $videoStyles = $this->videoStyles();

        return '<video style="' . $videoStyles .  '"  class="'. $this->classes . '" autoplay="" loop="" muted="" playsinline="" controlslist="nodownload,nofullscreen,noremoteplayback">
            <source src="' . \Storage::url($this->content) . '" type="video/mp4">
            Your browser does not support the video tag.
        </video>';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);

        $styles .= 'display:flex; flex-direction: column;';

        return $styles;
    }

    public function rules()
    {
        return [
            'title' => 'required',
        ];
    }
}
