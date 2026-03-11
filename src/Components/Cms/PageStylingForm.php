<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageStylingForm extends Form
{
    protected $pageId;

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function handle()
    {
        $styleModel = $this->model->styles ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $this->model->styles()->save($styleModel);

        $this->model->exterior_background_color = request('exterior_background_color', $this->model->getExteriorBackgroundColor());
        $this->model->save();
    }

    public function render()
    {
        return _Rows(
            $this->colorsGroup(),
            $this->typographyGroup(),
            $this->layoutGroup(),
            $this->submitSection(),
            $this->designTabStyles(),
        )->class('vlDesignTab');
    }

    protected function colorsGroup()
    {
        return _Rows(
            _Html('cms::cms.colors')->class('vlDesignGroupTitle'),

            _Rows(
                _Html('cms::cms.content-background-color')->class('vlDesignLabel'),
                _Flex(
                    _Div()->class('vlDesignSwatch')->style('background:' . $this->model->getContentBackgroundColor()),
                    _Input()->type('color')->value($this->model->getContentBackgroundColor())->name('background-color', false)
                        ->class('vlDesignColorInput'),
                )->class('vlDesignColorRow'),
            )->class('vlDesignField'),

            _Rows(
                _Html('cms::cms.exterior-background-color')->class('vlDesignLabel'),
                _Flex(
                    _Div()->class('vlDesignSwatch')->style('background:' . $this->model->getExteriorBackgroundColor()),
                    _Input()->type('color')->value($this->model->getExteriorBackgroundColor())->name('exterior_background_color')
                        ->class('vlDesignColorInput'),
                )->class('vlDesignColorRow'),
            )->class('vlDesignField'),

            _Rows(
                _Html('cms::cms.text-color')->class('vlDesignLabel'),
                _Flex(
                    _Div()->class('vlDesignSwatch')->style('background:' . $this->model->getTextColor()),
                    _Input()->type('color')->value($this->model->getTextColor())->name('color', false)
                        ->class('vlDesignColorInput'),
                )->class('vlDesignColorRow'),
            )->class('vlDesignField'),

            _Rows(
                _Html('cms::cms.link-color')->class('vlDesignLabel'),
                _Flex(
                    _Div()->class('vlDesignSwatch')->style('background:' . $this->model->getLinkColor()),
                    _Input()->type('color')->value($this->model->getLinkColor())->name('link-color', false)
                        ->class('vlDesignColorInput'),
                )->class('vlDesignColorRow'),
            )->class('vlDesignField'),
        )->class('vlDesignGroup');
    }

    protected function typographyGroup()
    {
        return _Rows(
            _Html('cms::cms.typography')->class('vlDesignGroupTitle'),

            _Rows(
                _Html('cms::cms.font-family')->class('vlDesignLabel'),
                _Select()->name('font-family', false)
                    ->options($this->fontFamilyOptions())
                    ->default($this->model->getStyleProperty('font_family') ?: 'system')
                    ->class('vlDesignSelect'),
            )->class('vlDesignField'),

            _Rows(
                _Html('cms::cms.font-size')->class('vlDesignLabel'),
                _Flex(
                    _InputNumber()->value($this->model->getFontSize())->name('font-size', false)
                        ->class('vlDesignNumberInput'),
                    _Html('px')->class('vlDesignUnit'),
                )->class('items-center gap-2'),
            )->class('vlDesignField'),
        )->class('vlDesignGroup');
    }

    protected function layoutGroup()
    {
        return _Rows(
            _Html('cms::cms.layout')->class('vlDesignGroupTitle'),

            _Rows(
                _Html('cms::cms.content-max-width')->class('vlDesignLabel'),
                _Flex(
                    _InputNumber()->value($this->model->getContentMaxWidth())->name('content-max-width', false)
                        ->class('vlDesignNumberInput'),
                    _Html('px')->class('vlDesignUnit'),
                )->class('items-center gap-2'),
            )->class('vlDesignField'),
        )->class('vlDesignGroup');
    }

    protected function submitSection()
    {
        return _Rows(
            _SubmitButton('cms::cms.apply-design')
                ->class('vlDesignSaveBtn w-full')
                ->onSuccess(fn($e) => $e->run('() => {
                    if (window.vlEmailEditor) vlEmailEditor.showToast("'.__('cms::cms.saved-successfully').'");
                    setTimeout(() => window.location.reload(), 500);
                }')),
        )->class('vlDesignActions');
    }

    protected function fontFamilyOptions()
    {
        return [
            'system' => 'System Default',
            'arial' => 'Arial',
            'helvetica' => 'Helvetica',
            'georgia' => 'Georgia',
            'times' => 'Times New Roman',
            'verdana' => 'Verdana',
            'trebuchet' => 'Trebuchet MS',
            'tahoma' => 'Tahoma',
        ];
    }

    protected function designTabStyles()
    {
        return _Html('<style>
            .vlDesignTab {
                padding: 0;
            }
            .vlDesignGroup {
                padding: 16px;
                border-bottom: 1px solid #f3f4f6;
            }
            .vlDesignGroupTitle {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6b7280;
                margin-bottom: 12px;
            }
            .vlDesignField {
                margin-bottom: 10px;
            }
            .vlDesignField:last-child {
                margin-bottom: 0;
            }
            .vlDesignLabel {
                font-size: 12px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 4px;
            }
            .vlDesignColorRow {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .vlDesignSwatch {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
            }
            .vlDesignColorInput {
                height: 32px !important;
                flex: 1 !important;
                padding: 2px !important;
                border-radius: 6px !important;
                border: 1px solid #e5e7eb !important;
                cursor: pointer;
            }
            .vlDesignSelect {
                font-size: 13px !important;
                padding: 6px 10px !important;
                border-radius: 6px !important;
                border: 1px solid #e5e7eb !important;
            }
            .vlDesignNumberInput {
                width: 80px !important;
                font-size: 13px !important;
                padding: 6px 10px !important;
                border-radius: 6px !important;
                border: 1px solid #e5e7eb !important;
                background: #ffffff !important;
            }
            .vlDesignUnit {
                font-size: 12px;
                color: #9ca3af;
                font-weight: 500;
            }
            .vlDesignActions {
                padding: 16px;
            }
            .vlDesignSaveBtn {
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
                font-size: 13px !important;
                padding: 10px !important;
            }
            .vlDesignSaveBtn:hover {
                background: #1d4ed8 !important;
            }
        </style>');
    }
}
