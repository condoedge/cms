<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class StylePageItemForm extends Form
{
    protected $styleModel = null;

    protected $pageId;
    protected $blockType;

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageItemModel::find($this->modelKey()) ?? PageItemModel::make());

        $this->styleModel = $this->model->styles ?? null;

        $this->pageId = $this->prop('page_id') ?? $this->model->page_id;
        $this->blockType = $this->prop('block_type') ?? $this->model->block_type;
    }

    public function render()
    {
        $this->model->block_type = $this->blockType;
        $this->model->page_id = $this->pageId;

        return _Rows(
            _Button('cms::cms.clear')->selfPost('clearStyles')->inPanel('item_styles_form')->class('mb-4'),
            $this->model->getPageItemType() && $this->model->getPageItemType()::ONLY_CUSTOM_STYLES ? null : 
            _Rows(
                _InputNumber('cms::cms.font-size')->name('font-size', false)->default($this->model->getFontSize())->class('mb-2 whiteField'),
                _Rows(
                    _Html('cms::cms.background-color')->class('font-semibold mb-1 text-sm'),
                    _ButtonGroup()
                        ->optionClass('px-4 py-2 text-center cursor-pointer')
                        ->selectedClass('bg-level3 text-white font-medium', 'bg-gray-200 text-level3 font-medium')
                        ->class('mb-1')->options([
                            'transparent' => __('cms::cms.transparent'),
                            'color' => __('cms::cms.color'),
                        ])->default($this->model->getBackgroundColor() == 'transparent' ? 'transparent' : 'color')->name('background-color-type', false)->selfGet('getBackgroundInputs')->inPanel('background_inputs'),
                    _Panel(
                        $this->model->getBackgroundColor() == 'transparent' ? null : 
                            _Input()->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('mb-2 whiteField'),
                    )->id('background_inputs')
                ),
                _Columns(
                    _Input('cms::cms.text-color')->type('color')->default($this->model->getTextColor())->name('color', false)->class('mb-2 whiteField'),
                )->class('!mb-0'),
                _Select('cms::cms.text-align')->name('text-align', false)->default($this->model?->getStyleProperty('text_align') ?: 'center')
                    ->options([
                        'left' => __('cms::cms.left'),
                        'center' => __('cms::cms.center'),
                        'right' => __('cms::cms.right'),
                    ])->class('mb-2 whiteField'),
                _Rows(
                    $this->extraInputs(),
                ),
            )->class('mb-4'),
            _Rows(
                _Html('cms::cms.custom-padding-and-styles')->class('font-semibold mb-4'),
                _Html('cms::cms.padding-px')->class('font-semibold text-sm mb-1'),
                _Columns(
                    _Input()->placeholder('cms::cms.padding-top')->name('padding-top', false)->default($this->model?->getStyleProperty('padding_top_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.padding-right')->name('padding-right', false)->default($this->model?->getStyleProperty('padding_right_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.padding-bottom')->name('padding-bottom', false)->default($this->model?->getStyleProperty('padding_bottom_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.padding-left')->name('padding-left', false)->default($this->model?->getStyleProperty('padding_left_raw'))->class('whiteField'),
                ),
                _Html('cms::cms.margin-px')->class('font-semibold text-sm mb-1'),
                _Columns(
                    _Input()->placeholder('cms::cms.margin-top')->name('margin-top', false)->default($this->model?->getStyleProperty('margin_top_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.margin-right')->name('margin-right', false)->default($this->model?->getStyleProperty('margin_right_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.margin-bottom')->name('margin-bottom', false)->default($this->model?->getStyleProperty('margin_bottom_raw'))->class('whiteField'),
                    _Input()->placeholder('cms::cms.margin-left')->name('margin-left', false)->default($this->model?->getStyleProperty('margin_left_raw'))->class('whiteField'),
                ),
                // _Input()->placeholder('cms::cms.styles')
                //     ->name('styles', false)
                //     ->class('whiteField'),
                _Input()->placeholder('cms::cms.classes')->name('classes')->class('whiteField'),

                _Panel(
                    !$this->model?->getPageItemType()?->blockTypeEditorStylesElement() ? null : _Rows(
                        _Html('cms::cms.styles-for-item')->class('text font-semibold mb-1'),
                        $this->model?->getPageItemType()?->blockTypeEditorStylesElement(),
                    )->class('mt-2')
                )->id(PageItemForm::ITEM_FORM_STYLES_ID),

                _Input('cms::cms.constructed-styles')->class('disabled mt-2')->name('actual_styles', false)->value((string) $this->styleModel?->content)->attr(['disabled' => true]),

            )->class('bg-gray-100 px-4 pb-4'),
        );
    }

    protected function extraInputs()
    {
        return [];
    }

    public function getBackgroundInputs()
    {
        $type = request('background-color-type');
        
        return $type == 'transparent' ? _Hidden()->name('background-color', false)->value('transparent') : _Input()->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('mb-2 whiteField');
    }

    public function clearStyles()
    {
        if($this->styleModel) {
            $this->styleModel->content = "";
            $this->styleModel->save();
        }

        return PageEditor::getItemStylesFormComponent($this->prefixGroup, $this->model->id, [
            'page_id' => $this->pageId,
            'block_type' => $this->blockType,
        ]);
    }
}
