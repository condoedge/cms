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
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('↓ Bottom')->name('padding-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('← Left')->name('padding-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('→ Right')->name('padding-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
            )->class('vlSpacingControl vlSpacingPadding'),

            _Html('cms::cms.margin-px')->class($hidePadding ? 'vlStyleSubLabel' : 'vlStyleSubLabel mt-3'),
            _Div(
                _Input()->placeholder('↑ Top')->name('margin-top' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_top' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('↓ Bottom')->name('margin-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('← Left')->name('margin-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('→ Right')->name('margin-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
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
                _Input('cms::cms.classes')->name('classes')->class('mb-2'),
                _Link('cms::cms.clear-styles')->icon(_Sax('refresh', 14))
                    ->selfPost('clearStyles')->inPanel('item_styles_form')
                    ->class('vlClearStylesBtn'),
            )->class('hidden'),
        )->class('mb-3');
    }

    protected function emailEditorStyleOverrides()
    {
        return null;
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
