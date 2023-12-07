<?php

namespace App\Cms\ItemTypes;

use App\Models\Cms\PageItem;
use App\Cms\PageItemType;

class KompoItem extends PageItemType
{
    public const ITEM_NAME = 'komponent';
    public const ITEM_TITLE = 'newsletter.component';
    public const ITEM_DESCRIPTION = 'newsletter.internal-pre-built-components';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = $pageItem?->title ?: '';
    }

    public function blockTypeEditorElement()
    {
       return _Rows(
            _Input('newsletter.component-name')->name($this->nameTitle, $this->interactsWithPageItem),
        );
    }

    protected function toElement()
    {
       return _Rows(
            _Flex(_Badge('Component'))->class('mb-2'),
            _Html($this->content),
        );
    }

    public function authorize()
    {
        return [
            'create' => true,
            'update' => [auth()->user()->isAdmin(), 'newsletter.sorry-only-the-developpers-can-edit-this-type-of-block'],
            'delete' => true,
        ];
    }

    public function toHtml(): string
    {
        return '';
    }
}
