<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageDesignForm extends Form
{   
    public $id = 'page_design_form';

    public const PREVIEW_PAGE_PANEL = 'preview_page_panel';
    public const PAGE_ITEM_PANEL = 'page_item_panel';

    public function create(){
        $this->model(PageModel::find($this->prop('id')) ?? PageModel::make());
    }

    public function render()
    {
        return _Div(
            _Panel(
                PageEditor::getPagePreviewComponent([
                    'page_id' => $this->model?->id,
                    'panel_id' => static::PAGE_ITEM_PANEL,
                    'with_editor' => true
                ]),
            )->id(static::PREVIEW_PAGE_PANEL)->class('w-1/2 mt-4'),
            _Card(
                _Panel(
                    $this->getPageItemForm()
                )->id(static::PAGE_ITEM_PANEL),
            )->class('px-8 py-6 mt-4 w-1/2 bg-gray-100'),
        )->class('vlFlex gap-4 w-full items-start');
    }

    public function getPageItemForm()
    {
        return PageEditor::getPageItemFormComponent(null, [
            'page_id' => $this->model?->id,
            'update_order' => true
        ]);
    }
}
