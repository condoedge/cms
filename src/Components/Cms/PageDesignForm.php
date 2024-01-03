<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageDesignForm extends Form
{
    public $id = 'page_design_form';
    protected $prefixGroup = "";

    public const PREVIEW_PAGE_PANEL = 'preview_page_panel';
    public const PAGE_ITEM_PANEL = 'page_item_panel';

    public function created(){
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        return _Rows(
            _Div(
                _Panel(
                    PageEditor::getPagePreviewComponent($this->prefixGroup, [
                        'page_id' => $this->model?->id,
                        'panel_id' => static::PAGE_ITEM_PANEL,
                        'with_editor' => true
                    ]),
                )->id(static::PREVIEW_PAGE_PANEL)->class('w-1/2 mt-4'),
                _Card(
                    _Panel(
                        $this->getPageItemForm(),
                    )->id(static::PAGE_ITEM_PANEL),
                )->class('px-8 py-6 mt-4 w-1/2 bg-gray-100'),
            )->class('vlFlex gap-4 w-full items-start ' . ($this->model?->id ? '' : 'p-6')),
            $this->model?->id ? null : _Html('cms::cms.first-save-page')->class('text-4xl flex justify-center items-center font-semibold bg-opacity-80 bg-white text-gray-800 absolute rounded-lg p-4 w-full h-full'),
        )->class('relative');
    }

    public function getPageItemForm()
    {
        return PageEditor::getPageItemFormComponent($this->prefixGroup, null, [
            'page_id' => $this->model?->id,
            'update_order' => true
        ]);
    }
}
