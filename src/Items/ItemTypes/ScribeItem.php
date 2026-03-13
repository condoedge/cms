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

    /**
     * Render the Scribe embed for the editor preview (iframe).
     */
    protected function toElementHtml(): string
    {
        $height = $this->pageItem?->styles?->content?->height_raw ?: 740;
        $uniqueId = uniqid('scribe-item-');

        return '<div>
            <div id="loading-'.$uniqueId.'" style="display: flex; justify-content:center; margin-top: 50px;">' . _Spinner('w-16 h-16')->__toHtml() . '</div>
            <iframe onload="$(\'#loading-'.$uniqueId.'\').fadeOut()" src="https://scribehow.com/embed/' .
            $this->content .
            '?as=scrollable&skipIntro=true&removeLogo=true" width="100%" frameborder="0" height="' . $height . '"></iframe>
        </div>';
    }

    /**
     * Render the Scribe item for email output (link instead of iframe).
     * iframes are not supported in email clients.
     */
    public function toHtml(): string
    {
        $scribeUrl = 'https://scribehow.com/shared/' . htmlspecialchars($this->content, ENT_QUOTES);

        return '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center" style="padding:20px 0;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td align="center" style="background-color:#2563eb; border-radius:8px; padding:14px 30px;">
                        <a href="' . $scribeUrl . '" target="_blank" style="color:#ffffff; text-decoration:none; font-size:15px; font-weight:600;">' . __('cms::cms.view-guide') . '</a>
                    </td></tr></table>
                </td>
            </tr>
        </table>';
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
