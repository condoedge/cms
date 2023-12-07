<?php

namespace Anonimatrix\PageEditor\Components;

use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageItemForm extends Form
{
    public $model = PageItem::class;

    protected $refresh = true;
    protected $pageId;
    protected $updateOrder;
    protected const ITEM_FORM_PANEL_ID = 'itemFormPanel';
    protected const ITEM_FORM_STYLES_ID = 'itemFormStyles';

    public function created()
    {
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

        // $this->model->getPageItemType()?->beforeSave($this->model); This was replaced with observers
    }

    public function afterSave()
    {
        // $this->model->getPageItemType()?->afterSave($this->model); This was replaced with observers
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
                    _InputNumber('campaign.font-size')->name('font_size')->value($this->model->getFontSize())->class('mb-2'),
                    _Input('campaign.background-color')->type('color')->value($this->model->getBackgroundColor())->name('background_color')->class('mb-2'),
                    _Columns(
                        _Input('campaign.title-color')->type('color')->value($this->model->getTitleColor())->name('title_color')->class('mb-2'),
                        _Input('campaign.text-color')->type('color')->value($this->model->getTextColor())->name('text_color')->class('mb-2'),
                    )->class('!mb-0'),
                    _Columns(
                        _Input('campaign.button-color')->type('color')->value($this->model->getButtonColor())->name('button_color'),
                        _Input('campaign.link-color')->type('color')->value($this->model->getLinkColor())->name('link_color'),
                    )->class('!mb-0'),
                    _Card(
                        _Html('campaign.custom-padding-and-styles')->class('text-sm font-semibold mb-4'),
                        _Html('campaign.padding-px')->class('font-semibold text-sm mb-1'),
                        _Columns(
                            _Input()->placeholder('campaign.top')->name('padding_top')->class('whiteField'),
                            _Input()->placeholder('campaign.right')->name('padding_right')->class('whiteField'),
                            _Input()->placeholder('campaign.bottom')->name('padding_bottom')->class('whiteField'),
                            _Input()->placeholder('campaign.left')->name('padding_left')->class('whiteField'),
                        ),
                        _Input()->placeholder('campaign.styles')
                            ->name('styles', false)
                            ->value((string) $this->model->styles)
                            ->class('whiteField'),
                        _Input()->placeholder('campaign.classes')->name('classes')->class('whiteField'),
                    )->class('bg-gray-100 p-4'),
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
        return new PagePreview([
            'page_id' => $this->pageId,
            'panel_id' => PageDesignForm::PAGE_ITEM_PANEL,
            'with_editor' => true
        ]);
    }

    public function itemForm()
    {
        if(!$this->isValidBlockType()) {
            return _Rows();
        }

        $item = PageItem::blockTypes()[request('block_type')];
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

        $item = PageItem::blockTypes()[request('block_type')];
        $item = new $item($this->model);

        return _Rows(
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItem::blockTypes()->has($blockType);
    }
}
