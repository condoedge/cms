<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;

class CKItem extends PageItemType
{
    public const ITEM_NAME = 'ck';
    public const ITEM_TITLE = 'newsletter.text-editor';
    public const ITEM_DESCRIPTION = 'newsletter.text-subtitles-lists-and-more';
    public const CUSTOM_CLASSES = false;

    public function __construct(\Anonimatrix\PageEditor\Models\PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->setHtmlElementsStyles('a', 'color: ' . $this->pageItem->getLinkColor() . '!important;');
    }

    public function blockTypeEditorElement()
    {
        $item = _CKEditorPageItem('Content')->name($this->nameContent, $this->interactsWithPageItem)
            ->withoutHeight()
            ->prependToolbar(['fontColor', 'fontBackgroundColor']);

        if($this->valueContent) $item = $item->default(json_decode($this->valueContent));

        return $item;
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _Input('translate.page-editor.link-color')->type('color')->default($this->pageItem->getLinkColor())->name('link-color', false)->class('mb-2 whiteField'),
        );
    }

    protected function toElement($withEditor = null)
    {
        return _Html($this->content)->replaceCKEditorContent($this->variables)
            ->class('ckEditorContent');
    }

    public function toHtml(): string
    {
        $text = replaceAllMentions($this->content, $this->variables);

        return '<div style="' . $this->styles . '" class="'. $this->classes . ' ckEditor">' . $text . '</div>';
    }

    public function beforeMountInGroup($groupItem)
    {
        $this->setHtmlElementsStyles('a', 'color: ' . $groupItem->getLinkColor() . '!important;');
    }

    public function setHtmlElementsStyles($tag, $styles)
    {
        $this->content = preg_replace_callback('/<'.$tag.'(.*?)>/', function($matches) use ($styles) {
            $element = $matches[0];

            if(strpos($element, 'style="') === false) $element = str_replace('>', ' style="' . $styles . '">', $element);
            else $element = preg_replace('/style="(.*?)"/', 'style="$1;' . $styles . '"', $element);

            return $element;
        }, $this->content);
    }

    public function defaultClasses($pageItem): string
    {
        return '';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'text-align: ' . $pageItem->text_align . ';';

        return $styles;
    }

    public function rules()
    {
        return [
            'content' => 'required',
        ];
    }
}
