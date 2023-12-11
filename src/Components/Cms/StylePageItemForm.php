<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Kompo\Form;

class StylePageItemForm extends Form
{
    public function created()
    {
        $this->model(PageItemModel::find($this->modelKey()) ?? PageItemModel::make());
    }

    public function render()
    {
        $style = $this->model->styles?->content;

        return _Rows(
            _Button('campaign.clear')->selfPost('clearStyles')->refresh()->class('mb-4'),
            _InputNumber('campaign.font-size')->name('font-size', false)->default($this->model->getDefaultFontSize())->class('mb-2'),
            _Input('campaign.background-color')->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('mb-2'),
            _Columns(
                _Input('campaign.text-color')->type('color')->default($style->color)->name('color', $this->model->getTextColor())->class('mb-2'),
            )->class('!mb-0'),
            _Rows(
                $this->extraInputs(),
            ),
            _Card(
                _Html('campaign.custom-padding-and-styles')->class('text-sm font-semibold mb-4'),
                _Html('campaign.padding-px')->class('font-semibold text-sm mb-1'),
                _Columns(
                    _Input()->placeholder('campaign.top')->name('padding-top', false)->default($style->padding_top)->class('whiteField'),
                    _Input()->placeholder('campaign.right')->name('padding-right', false)->default($style->padding_top)->class('whiteField'),
                    _Input()->placeholder('campaign.bottom')->name('padding-bottom', false)->default($style->padding_top)->class('whiteField'),
                    _Input()->placeholder('campaign.left')->name('padding-left', false)->default($style->padding_top)->class('whiteField'),
                ),
                _Input()->placeholder('campaign.styles')
                    ->name('styles', false)
                    ->class('whiteField'),
                _Input()->placeholder('campaign.classes')->name('classes')->class('whiteField'),
            )->class('bg-gray-100 p-4'),
        );
    }

    protected function extraInputs()
    {
        return [];
    }

    public function clearStyles()
    {
        if(!$this->model->exists()) return;

        $this->model->content = "";
        $this->model->save();
    }
}
