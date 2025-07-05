<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;

class ScribeItem extends PageItemType
{
    public const ITEM_TAG = 'iframe';
    public const ITEM_NAME = 'scribe';
    public const ITEM_TITLE = 'cms::cms.scribe-item-title';
    public const ITEM_DESCRIPTION = 'cms::cms.scribe-item-sub';

    public const ONLY_CUSTOM_STYLES = true;

    public function blockTypeEditorElement()
    {
        return _Translatable('cms::cms.scribe-code')
            ->name($this->nameContent, $this->interactsWithPageItem)
            ->default(json_decode($this->valueContent));
    }


    protected function toElement($withEditor = null)
    {
        if ($withEditor) {
            return _Html(
                '<div style="position: absolute; height: 100%; width: 92%; min-height: 740px;"></div>' .
                    $this->toHtml(),
            );
        }

        return _Html($this->toHtml());
    }

    public function toHtml(): string
    {
        $height = $this->pageItem?->styles?->content?->height_raw ?? 740;
        $uniqueId = uniqid('scribe-item-');

        return '<div>
            <div id="loading-'.$uniqueId.'" style="display: flex; justify-content:center; margin-top: 50px;">' . _Spinner('w-16 h-16')->__toHtml() . '</div>
            <iframe onload="$(\'#loading-'.$uniqueId.'\').fadeOut()" src="https://scribehow.com/embed/' .
            $this->content .
            '?as=scrollable&skipIntro=true&removeLogo=true" width="100%" frameborder="0" height="' . $height . '"></iframe>
        </div>';
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
