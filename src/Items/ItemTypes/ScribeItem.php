<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;

class ScribeItem extends PageItemType
{
    public const ITEM_TAG = 'iframe';
    public const ITEM_NAME = 'scribe';
    public const ITEM_TITLE = 'wiki.scribe-item-title';
    public const ITEM_DESCRIPTION = 'wiki.scribe-item-sub';

    public const ONLY_CUSTOM_STYLES = true;

    public function blockTypeEditorElement()
    {
        return _Input('wiki.scribe-code')
            ->name($this->nameContent, $this->interactsWithPageItem)
            ->default($this->valueContent);
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
        return '<iframe src="https://scribehow.com/embed/' .
                $this->content .
        '?as=scrollable&skipIntro=true&removeLogo=true" width="100%" height="740" frameborder="0"></iframe>';
    }

    public function rules()
    {
        return [
            'content' => 'required',
        ];
    }

}
