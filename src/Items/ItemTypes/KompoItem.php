<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Items\PageItemType;

class KompoItem extends PageItemType
{
    public const ITEM_NAME = 'komponent';
    public const ITEM_TITLE = 'cms::cms.items.component';
    public const ITEM_DESCRIPTION = 'cms::cms.items.internal-pre-built-components';

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

    protected function toElement($withEditor = null)
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
            'update' => [auth()->user()->isCmsAdmin(), 'newsletter.sorry-only-the-developpers-can-edit-this-type-of-block'],
            'delete' => true,
        ];
    }

    public function toHtml(): string
    {
        return '';
    }
}
