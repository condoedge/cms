<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class StylePageItemForm extends Form
{
    protected $styleModel = null;

    public function created()
    {
        $this->model(PageItemModel::find($this->modelKey()) ?? PageItemModel::make());

        $this->styleModel = $this->model->styles ?? null;
    }

    public function render()
    {
        return _Rows(
            _Button('translate.page-editor.clear')->selfPost('clearStyles')->inPanel('item_styles_form')->class('mb-4'),
            _InputNumber('translate.page-editor.font-size')->name('font-size', false)->default($this->model->getFontSize())->class('mb-2 whiteField'),
            _Input('translate.page-editor.background-color')->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('mb-2 whiteField'),
            _Columns(
                _Input('translate.page-editor.text-color')->type('color')->default($this->model->getTextColor())->name('color', false)->class('mb-2 whiteField'),
            )->class('!mb-0'),
            _Select('translate.page-editor.text-align')->name('text-align', false)->default($this->styleModel?->text_align ?: 'center')
                ->options([
                    'left' => 'translate.page-editor.left',
                    'center' => 'translate.page-editor.center',
                    'right' => 'translate.page-editor.right',
                ])->class('mb-2 whiteField'),
            _Rows(
                $this->extraInputs(),
            ),
            _Rows(
                _Html('translate.page-editor.custom-padding-and-styles')->class('text-sm font-semibold mb-4'),
                _Html('translate.page-editor.padding-px')->class('font-semibold text-sm mb-1'),
                _Columns(
                    _Input()->placeholder('translate.page-editor.top')->name('padding-top', false)->default($this->styleModel?->padding_top_raw)->class('whiteField'),
                    _Input()->placeholder('translate.page-editor.right')->name('padding-right', false)->default($this->styleModel?->padding_right_raw)->class('whiteField'),
                    _Input()->placeholder('translate.page-editor.bottom')->name('padding-bottom', false)->default($this->styleModel?->padding_bottom_raw)->class('whiteField'),
                    _Input()->placeholder('translate.page-editor.left')->name('padding-left', false)->default($this->styleModel?->padding_left_raw)->class('whiteField'),
                ),
                _Input()->placeholder('translate.page-editor.styles')
                    ->name('styles', false)
                    ->class('whiteField'),
                _Input()->placeholder('translate.page-editor.classes')->name('classes')->class('whiteField'),

                _Panel(
                    !$this->model?->getPageItemType()?->blockTypeEditorStylesElement() ? null : _Rows(
                        _Html('translate.page-editor.styles-for-item')->class('text-sm font-semibold mb-1'),
                        $this->model?->getPageItemType()?->blockTypeEditorStylesElement(),
                    )->class('mt-2')
                )->id(PageItemForm::ITEM_FORM_STYLES_ID),

                _Input('translate.page-editor.constructed-styles')->class('disabled mt-2')->name('actual_styles', false)->value((string) $this->styleModel?->content)->attr(['disabled' => true]),

            )->class('bg-gray-100 p-4'),
        );
    }

    protected function extraInputs()
    {
        return [];
    }

    public function setGenericStyles()
    {

    }

    public function clearStyles()
    {
        if(!$this->styleModel) return;

        $this->styleModel->content = "";
        $this->styleModel->save();

        return PageEditor::getItemStylesFormComponent($this->model->id);
    }
}
