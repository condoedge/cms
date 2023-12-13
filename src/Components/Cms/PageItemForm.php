<?php

namespace Anonimatrix\PageEditor\Components\Cms;

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
    public const ITEM_FORM_PANEL_ID = 'itemFormPanel';
    public const ITEM_FORM_STYLES_ID = 'itemFormStyles';

    public function created()
    {
        $this->model(PageItemModel::find($this->modelKey()) ?? PageItemModel::make());

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
    }

    public function afterSave()
    {
        $styleModel = $this->model->styles ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $styleModel->content .= request('styles');

        $this->model->styles()->save($styleModel);
    }

    public function render()
    {
        $types = PageEditor::getOptionsTypes();

        return _Tabs(
            _Tab(
                _Rows(
                    _Columns(
                        _Select('translate.page-editor.zone-type')->options(
                            $types,
                        )->name('block_type')->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID) && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form') && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID))->col($this->model->id ? 'col-md-8' : 'col-md-12'),
                        $this->model->id ? _DeleteButton('translate.page-editor.clear')->byKey($this->model)->refresh('page_design_form')->col('col-md-4') : null,
                    )->class('items-center'),
                    _Input('translate.page-editor.zone-name')->name('name_pi'),
                    _Panel(
                        $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : _Html(''),
                    )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
                    _SubmitButton('translate.page-editor.save-zone')->class('ml-auto mt-3')
                        ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                )
            )->label('translate.page-editor.zone-content'),
            _Tab(
                _Rows(
                    _Panel(
                        $this->getStyleFormComponent(),
                    )->id('item_styles_form')->class('mt-4'),
                    _FlexBetween(
                        _Button('translate.page-editor.set-generic-styles-to-block')->selfPost('setGenericStyles')->withAllFormValues(),
                        _SubmitButton('translate.page-editor.save')->class('ml-auto')
                            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                    )->class('gap-4 mt-3'),
                )->class('!mb-2')
            )->label('translate.page-editor.zone-styles'),
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

    public function getStyleFormComponent()
    {
        return PageEditor::getItemStylesFormComponent($this->model->id, [
            'page_id' => $this->pageId,
            'block_type' => request('block_type') ?? $this->model->block_type,
        ]);
    }

    public function setGenericStyles()
    {
        $styleModel = PageItemStyleModel::getGenericStylesOfType($this->model->getPageItemType()::class, $this->pageId) ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);
        
        $styleModel->block_type = request('block_type');
        $styleModel->page_id = $this->pageId;
        $styleModel->save();
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

        return !$item->blockTypeEditorStylesElement() ? null : _Rows(
            _Html('translate.page-editor.styles-for-item')->class('text-sm font-semibold mb-1'),
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItemModel::blockTypes()->has($blockType);
    }
}
