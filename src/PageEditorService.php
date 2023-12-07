<?php

namespace Anonimatrix\PageEditor;

class PageEditorService
{
    public function getAvailableTypes()
    {
        return collect(config('page-editor.types'))->filter(function ($item) {
            return !in_array($item, config('page-editor.hidden_types'));
        });
    }

    public function getOptionsTypes()
    {
        return collect($this->getAvailableTypes())->mapWithKeys(function ($item) {
            return [$item::ITEM_NAME => __($item::ITEM_TITLE)];
        })->toArray();
    }
}