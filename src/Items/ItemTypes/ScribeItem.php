<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;

class ScribeItem extends PageItemType
{
    public const ITEM_TAG = 'iframe';
    public const ITEM_NAME = 'scribe';
    public const ITEM_TITLE = 'cms::cms.scribe-item-title';
    public const ITEM_DESCRIPTION = 'cms::cms.scribe-item-sub';
    public const ITEM_ICON = 'code-1';

    public const ONLY_CUSTOM_STYLES = true;

    public function blockTypeEditorElement()
    {
        return _Translatable('cms::cms.scribe-code')
            ->name($this->nameContent, $this->interactsWithPageItem)
            ->default(json_decode($this->valueContent));
    }


    protected function toElement($withEditor = null)
    {
        $html = $this->toElementHtml();

        if ($withEditor) {
            return _Html(
                '<div style="position: absolute; height: 100%; width: 92%; min-height: 740px;"></div>' . $html,
            );
        }

        return _Html($html);
    }

    protected function toElementHtml(): string
    {
        $contentId = htmlspecialchars((string) $this->content, ENT_QUOTES);

        return view('cms::items.scribe-preview', [
            'uniqueId' => uniqid('scribe-item-'),
            'height' => $this->pageItem?->styles?->content?->height_raw ?: 740,
            'scribeEmbedUrl' => 'https://scribehow.com/embed/'.$contentId.'?as=scrollable&skipIntro=true&removeLogo=true',
            'spinner' => _Spinner('w-16 h-16')->__toHtml(),
        ])->render();
    }

    // iframes aren't supported in email clients — render a CTA link to the Scribe page instead.
    public function toHtml(): string
    {
        return view('cms::items.scribe-email', [
            'scribeUrl' => 'https://scribehow.com/shared/'.htmlspecialchars($this->content, ENT_QUOTES),
            'label' => __('cms::cms.view-guide'),
        ])->render();
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _InputNumber('cms::cms.height-px')->name('height', false)->default($this->pageItem?->styles?->content->height_raw)->class('mb-2 whiteField'),
        );
    }

    public function rules()
    {
        return [
            'content' => 'required',
        ];
    }
}
