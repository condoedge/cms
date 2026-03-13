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

    protected function isImageBlock()
    {
        $type = $this->model->getPageItemType();
        return $type && defined(get_class($type).'::ITEM_NAME') && get_class($type)::ITEM_NAME === 'img';
    }

    public function render()
    {
        $this->model->block_type = $this->blockType;
        $this->model->page_id = $this->pageId;

        $isImage = $this->isImageBlock();

        return _Rows(
            $this->model->getPageItemType() && $this->model->getPageItemType()::ONLY_CUSTOM_STYLES ? null :
            _Rows(
                $isImage ? null : $this->colorsSection(),
                $isImage ? null : $this->typographySection(),
                $this->spacingSection($isImage),
                $this->blockSpecificStyles(),
                $this->responsiveSection(),
                $this->advancedSection(),
            ),
            $this->emailEditorStyleOverrides(),
        );
    }

    protected function colorsSection()
    {
        $bgColor = $this->model->getBackgroundColor();
        $isTransparent = $bgColor == 'transparent';

        return _Rows(
            _Html('cms::cms.colors')->class('vlStyleLabel mb-2'),
            _Flex(
                _Rows(
                    _Html('cms::cms.background-color')->class('vlStyleSubLabel'),
                    _ButtonGroup()
                        ->optionClass('vlBgOption')
                        ->selectedClass('vlBgOptionActive', 'vlBgOptionInactive')
                        ->options([
                            'transparent' => __('cms::cms.transparent'),
                            'color' => __('cms::cms.color'),
                        ])->default($isTransparent ? 'transparent' : 'color')
                        ->name('background-color-type', false)
                        ->class('mb-2')
                        ->onChange(fn($e) => $e->selfGet('getBackgroundInputs')->inPanel('background_inputs')),
                    _Panel(
                        $isTransparent ? _Hidden()->name('background-color', false)->value('transparent') :
                            _Input()->type('color')->default($bgColor)->name('background-color', false)->class('vlColorInput'),
                    )->id('background_inputs'),
                )->class('flex-1'),
                _Rows(
                    _Html('cms::cms.text-color')->class('vlStyleSubLabel'),
                    _Input()->type('color')->default($this->model->getTextColor())->name('color', false)
                        ->class('vlColorInput'),
                )->class('flex-1'),
            )->class('gap-3 !items-start'),
        )->class('mb-3');
    }

    protected function typographySection()
    {
        return _Rows(
            _Flex(
                _Rows(
                    _Html('cms::cms.font-size')->class('vlStyleLabel'),
                    _InputNumber()->name('font-size', false)->default($this->model->getFontSize()),
                )->class('flex-1'),
                _Rows(
                    _Html('cms::cms.text-align')->class('vlStyleLabel'),
                    _ButtonGroup()->name('text-align', false)
                        ->default($this->model?->getStyleProperty('text_align') ?: 'center')
                        ->options([
                            'left' => _Html()->icon(_Sax('textalign-left', 16)),
                            'center' => _Html()->icon(_Sax('textalign-center', 16)),
                            'right' => _Html()->icon(_Sax('textalign-right', 16)),
                        ])->optionClass('vlAlignBtn')
                        ->selectedClass('vlAlignBtnActive', 'vlAlignBtnInactive'),
                )->class('flex-1'),
            )->class('gap-3 mb-3'),
            $this->extraInputs(),
        );
    }

    protected function spacingSection($hidePadding = false)
    {
        return _Rows(
            _Html('cms::cms.spacing')->class('vlStyleLabel mb-2'),
            _Tabs(
                _Tab(
                    $this->spacingInputs('desktop', $hidePadding),
                )->label('cms::cms.desktop')->class('vlSpacingTabContent'),
                _Tab(
                    $this->spacingInputs('mobile', $hidePadding),
                )->label('cms::cms.mobile')->class('vlSpacingTabContent'),
            )->class('vlSpacingTabs'),
        )->class('mb-3');
    }

    protected function spacingInputs($device = 'desktop', $hidePadding = false)
    {
        $suffix = $device === 'mobile' ? '-mobile' : '';
        $defaultVal = $device === 'mobile' ? 0 : null;

        return _Rows(
            $hidePadding ? null : _Html('cms::cms.padding-px')->class('vlStyleSubLabel'),
            $hidePadding ? null : _Div(
                _Input()->placeholder('↑ Top')->name('padding-top' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_top' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('↓ Bottom')->name('padding-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('← Left')->name('padding-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('→ Right')->name('padding-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
            )->class('vlSpacingControl vlSpacingPadding'),

            _Html('cms::cms.margin-px')->class($hidePadding ? 'vlStyleSubLabel' : 'vlStyleSubLabel mt-3'),
            _Div(
                _Input()->placeholder('↑ Top')->name('margin-top' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_top' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('↓ Bottom')->name('margin-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('← Left')->name('margin-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
                _Input()->placeholder('→ Right')->name('margin-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput'),
            )->class('vlSpacingControl vlSpacingMargin'),
        );
    }

    protected function blockSpecificStyles()
    {
        $blockStylesEl = $this->model?->getPageItemType()?->blockTypeEditorStylesElement();

        if (!$blockStylesEl) return null;

        return _Rows(
            _Html('cms::cms.block-options')->class('vlStyleLabel mb-2'),
            $blockStylesEl,
        )->class('mb-3');
    }

    protected function responsiveSection()
    {
        return _Rows(
            _Html('cms::cms.responsive')->class('vlStyleLabel !mb-3'),
            _Rows(
                _Toggle('cms::cms.hide-on-mobile')->name('hide-on-mobile', false)
                    ->value((bool) ($this->model?->getStyleProperty('hide_on_mobile_raw') ?? false))
                    ->class('vlToggle w-full mb-3'),
                _Toggle('cms::cms.hide-on-desktop')->name('hide-on-desktop', false)
                    ->value((bool) ($this->model?->getStyleProperty('hide_on_desktop_raw') ?? false))
                    ->class('vlToggle w-full'),
            ),
        )->class('mb-3');
    }

    protected function advancedSection()
    {
        return _Rows(
            _Link('cms::cms.advanced')->class('vlStyleLabel vlAdvancedToggle')
                ->run('(el) => {
                    el.classList.toggle("vlAdvancedOpen");
                    el.nextElementSibling.classList.toggle("hidden");
                }'),
            _Rows(
                _Input('cms::cms.classes')->name('classes')->class('vlCompactInput mb-2'),
                _Link('cms::cms.clear-styles')->icon(_Sax('refresh', 14))
                    ->selfPost('clearStyles')->inPanel('item_styles_form')
                    ->class('vlClearStylesBtn'),
            )->class('hidden'),
        )->class('mb-3');
    }

    protected function emailEditorStyleOverrides()
    {
        return _Html('<style>
            /* Remove Kompo default card styling on form fields inside drawer */
            .vlEditorRightPanel .vlFormField {
                margin-top: 0 !important;
                margin-bottom: 4px !important;
            }
            .vlEditorRightPanel .vlFormLabel {
                font-size: 12px !important;
                color: #6b7280 !important;
                margin-bottom: 2px !important;
            }
            .vlEditorRightPanel .vlInputWrapper {
                background-color: #f3f4f6 !important;
            }
            .vlEditorRightPanel .vlInputWrapper .toggle-button {
                background-color: #f3f4f6 !important;
            }

            /* Style Labels */
            .vlStyleLabel {
                font-size: 11px;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                margin-bottom: 0.5;
                margin-top: 0.75rem;
            }
            .vlStyleSubLabel {
                font-size: 11px;
                font-weight: 600;
                color: #9ca3af;
                margin-bottom: 4px;
            }

            /* Color Inputs */
            .vlColorInput {
                height: 36px !important;
                cursor: pointer;
            }

            /* Compact Inputs */
            .vlCompactInput {
                font-size: 13px !important;
                padding: 6px 10px !important;
                border-radius: 6px !important;
                border: 1px solid #e5e7eb !important;
                background: #ffffff !important;
            }
            .vlCompactInput:focus {
                border-color: #93c5fd !important;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
            }

            /* Background Toggle */
            .vlBgOption {
                padding: 14px 12px !important;
                font-size: 13px !important;
                border-radius: 8px !important;
                cursor: pointer;
                text-align: center;
            }
            .vlBgOptionActive {
                background: #2563eb !important;
                color: #ffffff !important;
            }
            .vlBgOptionInactive {
                background: #f3f4f6 !important;
                color: #6b7280 !important;
            }

            /* Alignment Buttons */
            .vlEditorRightPanel .vlButtonGroup .vlInputWrapper {
                padding: 0 !important;
                background: transparent !important;
                border: none !important;
            }
            .vlEditorRightPanel .vlButtonGroup .vlOptionCont {
                display: flex !important;
                gap: 4px !important;
            }
            .vlEditorRightPanel .vlButtonGroup .vlOptionCont > .vlOption + .vlOption {
                border-left: none !important;
            }
            .vlAlignBtn {
                flex: 1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 6px !important;
                cursor: pointer !important;
                padding: 16px 0 !important;
            }
            .vlAlignBtn > div {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 100% !important;
                height: 100% !important;
            }
            .vlAlignBtnActive {
                background: #2563eb !important;
                color: #ffffff !important;
            }
            .vlAlignBtnInactive {
                background: #f3f4f6 !important;
                color: #6b7280 !important;
            }

            /* Spacing Visual Control */
            .vlSpacingTabs > ul > li {
                margin-right: 8px !important;
            }
            .vlSpacingTabs > ul > li > a {
                font-size: 11px !important;
                font-weight: 600 !important;
                padding: 6px 16px !important;
                display: inline-block !important;
            }
            .vlSpacingTabContent {
                padding: 8px 0 0 !important;
            }
            .vlSpacingControl {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding: 8px;
                border: 1.5px dashed #d1d5db;
                border-radius: 8px;
            }
            .vlSpacingPadding {
                border-color: #93c5fd;
                background: #f0f7ff;
            }
            .vlSpacingMargin {
                border-color: #fdba74;
                background: #fffbf0;
            }
            .vlSpacingCenter {
                display: contents;
            }
            .vlSpacingRow {
                display: contents;
            }
            .vlSpacingBox {
                display: none;
            }
            .vlSpacingInput {
                width: 100% !important;
                min-width: 0 !important;
                text-align: center !important;
                font-size: 13px !important;
            }
            .vlSpacingInput:focus {
                border-color: #93c5fd !important;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
            }
            .vlSpacingTop, .vlSpacingBottom {
                width: 100% !important;
            }

            /* Toggle */
            .vlToggle {
                font-size: 13px !important;
            }
            .vlToggleFullWidth {
                width: 100% !important;
                display: flex !important;
                justify-content: space-between !important;
                padding: 8px 12px !important;
                background: #f9fafb !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 8px !important;
            }

            /* Advanced Section */
            .vlAdvancedToggle {
                cursor: pointer;
                user-select: none;
            }
            .vlAdvancedToggle::after {
                content: " ▸";
            }
            .vlAdvancedToggle.vlAdvancedOpen::after {
                content: " ▾";
            }

            /* Clear Styles */
            .vlClearStylesBtn {
                font-size: 12px !important;
                color: #9ca3af !important;
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
            }
            .vlClearStylesBtn:hover {
                color: #dc2626 !important;
            }
        </style>');
    }

    protected function extraInputs()
    {
        return [];
    }

    public function getBackgroundInputs()
    {
        $type = request('background-color-type');

        return $type == 'transparent'
            ? _Hidden()->name('background-color', false)->value('transparent')
            : _Input()->type('color')->default($this->model->getBackgroundColor())->name('background-color', false)->class('vlColorInput');
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
