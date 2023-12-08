<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageItemForm extends Form
{
    protected $refresh = true;
    protected $pageId;
    protected $updateOrder;
    protected const ITEM_FORM_PANEL_ID = 'itemFormPanel';
    protected const ITEM_FORM_STYLES_ID = 'itemFormStyles';

    public function created()
    {
        $this->model(app('page-item-model'));

        $this->updateOrder = $this->prop('update_order');

        $this->pageId = $this->prop('page_id');
        $this->model->page_id = $this->pageId;

        $this->model->block_type = $this->model->block_type ?: request('block_type');
    }

    public function beforeSave()
    {
        if ($this->updateOrder) {
            $this->model->order = $this->model->page->pageItems()->count() - 1;
        }

        $this->model->title = request('title');
        $this->model->content = request('content');
        $this->model->styles = request('styles');

        if (!$this->model->styles) {
            $this->model->styles = PageItemStyleModel::make();
        }

        PageStyle::setStylesToModel($this->model->styles);
    }

    public function render()
    {
        $types = PageEditor::getOptionsTypes();

        return _Tabs(
            _Tab(
                _Rows(
                    _Columns(
                        _Select('campaign.zone-type')->options(
                            $types,
                        )->name('block_type')->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID) && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID))->col($this->model->id ? 'col-md-8' : 'col-md-12'),
                        $this->model->id ? _DeleteButton('campaign.clear')->byKey($this->model)->refresh('page_design_form')->col('col-md-4') : null,
                    )->class('items-center'),
                    _Input('campaign.zone-name')->name('name_pi'),
                    _Panel(
                        $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : _Html(''),
                    )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
                    _SubmitButton('campaign.save-zone')->class('ml-auto mt-3')
                        ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                )
            )->label('campaign.zone-content'),
            _Tab(
                _Rows(
                    PageEditor::getItemStylesFormComponent($this->model->styles?->id),
                    _Panel(
                        $this->model->id ? $this->model->getPageItemType()?->blockTypeEditorStylesElement() : _Html(''),
                    )->id(static::ITEM_FORM_STYLES_ID)->class('mt-4'),
                    _SubmitButton('campaign.save')->class('ml-auto mt-3')
                        ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                )->class('!mb-2')
            )->label('campaign.zone-styles'),
        );
    }

    public function getPagePreview()
    {
        return PageEditor::getPagePreviewComponent(
            [
                'page_id' => $this->pageId,
                'panel_id' => PageDesignForm::PAGE_ITEM_PANEL,
                'with_editor' => true
            ]
        );
    }

    public function itemForm()
    {
        if(!$this->isValidBlockType()) {
            return _Rows();
        }

        $item = PageItemModel::blockTypes()[request('block_type')];
        $item = new $item($this->model);

        return _Rows(
            $item->blockTypeEditorElement(),
        );
    }

    public function itemStylesForm()
    {
        if(!$this->isValidBlockType()) {
            return _Rows();
        }

        $item = PageItemModel::blockTypes()[request('block_type')];
        $item = new $item($this->model);

        return _Rows(
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItemModel::blockTypes()->has($blockType);
    }
}
