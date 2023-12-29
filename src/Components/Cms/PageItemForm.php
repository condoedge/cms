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

    protected $prefixGroup = "";

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
                        _Select('cms.zone-type')->options(
                            $types,
                        )->name('block_type')->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID) && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form') && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID))->col($this->model->id ? 'col-md-8' : 'col-md-12'),
                        $this->model->id ? _DeleteButton('cms.clear')->byKey($this->model)->refresh('page_design_form')->col('col-md-4') : null,
                    )->class('items-center'),
                    _Input('cms.zone-name')->name('name_pi'),
                    _Panel(
                        $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : _Html(''),
                    )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
                    _FlexBetween(
                        _SubmitButton('cms.save-zone-and-new')->class('ml-auto mt-3')
                            ->onSuccess(fn($e) => $e->selfGet('refreshItemForm')->inPanel(PageDesignForm::PAGE_ITEM_PANEL) && $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                        _SubmitButton('cms.save-zone')->class('ml-auto mt-3')
                            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                    )->class('gap-4'),
                )
            )->label('cms.zone-content'),
            _Tab(
                _Rows(
                    _Panel(
                        $this->getStyleFormComponent(),
                    )->id('item_styles_form'),
                    _FlexBetween(
                        _Button('cms.set-generic-styles-to-block')->selfPost('setGenericStyles')->withAllFormValues(),
                        _SubmitButton('cms.save')->class('ml-auto')
                            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                    )->class('gap-4 mt-3'),
                )->class('!mb-2')
            )->label('cms.zone-styles'),
        );
    }

    public function rules()
    {
        $itemRules = !$this->model->block_type ? [] : $this->model->getPageItemType()->rules();

        return [
            'block_type' => 'required',
            ...$itemRules,
        ];
    }

    public function refreshItemForm()
    {
        return PageEditor::getPageItemFormComponent($this->prefixGroup, null, [
            'update_order' => true,
            'page_id' => $this->pageId,
        ]);
    }

    public function getPagePreview()
    {
        return PageEditor::getPagePreviewComponent(
            $this->prefixGroup,
            [
                'page_id' => $this->pageId,
                'panel_id' => PageDesignForm::PAGE_ITEM_PANEL,
                'with_editor' => true
            ]
        );
    }

    public function getStyleFormComponent()
    {
        return PageEditor::getItemStylesFormComponent($this->prefixGroup, $this->model->id, [
            'page_id' => $this->pageId,
            'block_type' => request('block_type') ?? $this->model->block_type,
        ]);
    }

    public function setGenericStyles()
    {
        $styleModel = PageItemStyleModel::getGenericStylesOfType($this->model->getPageItemType()::class, $this->model->page?->team_id) ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);
        
        $styleModel->block_type = request('block_type');
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
            _Html('cms.styles-for-item')->class('text-sm font-semibold mb-1'),
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItemModel::blockTypes()->has($blockType);
    }
}
