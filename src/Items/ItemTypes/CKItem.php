<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Items\PageItemType;

class CKItem extends PageItemType
{
    public const ITEM_NAME = 'ck';
    public const ITEM_TITLE = 'cms::cms.items.text-editor';
    public const ITEM_DESCRIPTION = 'cms::cms.items.text-subtitles-lists-and-more';
    public const ITEM_ICON = 'text';
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
            _Input('cms::cms.link-color')->type('color')->default($this->pageItem->getLinkColor())->name('link-color', false)->class('mb-2 whiteField'),
        );
    }

    protected function toElement($withEditor = null)
    {
        $this->styles->removeProperties(['text-align']);

        return _Html($this->content)->replaceCKEditorContent($this->variables)
            ->class('ckEditorContent');
    }

    public function toHtml(): string
    {
        $this->styles->removeProperties(['text-align']);

        $text = replaceAllMentionsCms($this->content, $this->variables);

        return '<div style="' . $this->styles . '">' . $text . '</div>';
    }

    public function beforeMountInGroup($groupItem)
    {
        $this->setHtmlElementsStyles('a', 'color: ' . $groupItem->getLinkColor() . '!important;', true);

        // By default the editor doesn't set left align, because it assumes left is default. But we have different settings in the wrappers
        // So we need to explicitly set it here. But i created the concept of override so it doesn't set it if it's specifically set to something else
        $this->setHtmlElementsStyles('p', 'text-align: left;', false);
    }

    public function setHtmlElementsStyles($tag, $styles, $override = false)
    {
        $this->content = preg_replace_callback('/<'.$tag.'(.*?)>/', function($matches) use ($styles, $override) {
            $element = $matches[0];
            $separatedStatments = explode(';', $styles);

            if(strpos($element, 'style="') === false) $element = str_replace('>', ' style="' . $styles . '">', $element);
            else {
                foreach($separatedStatments as $statment) {
                    $statment = trim($statment);
                    if($statment === '') continue;

                    $styleHasProperty = preg_match('/'.$statment.'\s*:\s*([^;"]+);?/', $element);

                    if ($override && $styleHasProperty) {
                        $element = preg_replace('/'.$statment.'\s*:\s*([^;"]+);?/', $statment . ': ' . trim(explode(':', $statment)[1]) . ';', $element);
                    } elseif (!$styleHasProperty) {
                        $stylePosition = strpos($element, 'style="') + 7;
                        $element = substr_replace($element, $statment . ';', $stylePosition, 0);
                    }
                }
            }

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

        return $styles;
    }

    public function rules()
    {
        return [
            'content' => 'required',
        ];
    }
}
