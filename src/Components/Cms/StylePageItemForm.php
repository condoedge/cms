<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
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
            _Button('campaign.clear')->selfPost('clearStyles')->refresh()->class('mb-4'),
            _InputNumber('campaign.font-size')->name('font-size', false)->default($this->model->getFontSize())->class('mb-2'),
            _Input('campaign.background-color')->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('mb-2'),
            _Columns(
                _Input('campaign.text-color')->type('color')->default($this->model->getTextColor())->name('color', false)->class('mb-2'),
            )->class('!mb-0'),
            _Rows(
                $this->extraInputs(),
            ),
            _Card(
                _Html('campaign.custom-padding-and-styles')->class('text-sm font-semibold mb-4'),
                _Html('campaign.padding-px')->class('font-semibold text-sm mb-1'),
                _Columns(
                    _Input()->placeholder('campaign.top')->name('padding-top', false)->default($this->styleModel?->padding_top_raw)->class('whiteField'),
                    _Input()->placeholder('campaign.right')->name('padding-right', false)->default($this->styleModel?->padding_right_raw)->class('whiteField'),
                    _Input()->placeholder('campaign.bottom')->name('padding-bottom', false)->default($this->styleModel?->padding_bottom_raw)->class('whiteField'),
                    _Input()->placeholder('campaign.left')->name('padding-left', false)->default($this->styleModel?->padding_left_raw)->class('whiteField'),
                ),
                _Input()->placeholder('campaign.styles')
                    ->name('styles', false)
                    ->class('whiteField'),
                _Input()->placeholder('campaign.classes')->name('classes')->class('whiteField'),

                _Input('campaign.constructed-styles')->class('disabled')->name('actual_styles', false)->value((string) $this->styleModel?->content)->attr(['disabled' => true]),
            )->class('bg-gray-100 p-4'),
        );
    }

    protected function extraInputs()
    {
        return [];
    }

    public function clearStyles()
    {
        if(!$this->styleModel) return;

        $this->styleModel->content = "";
        $this->styleModel->save();
    }
}
