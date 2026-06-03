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

        if ($this->model->getPageItemType() && $this->model->getPageItemType()::ONLY_CUSTOM_STYLES) {
            return _Rows();
        }

        $isImage = $this->isImageBlock();
        $blockStyles = $this->model?->getPageItemType()?->blockTypeEditorStylesElement();

        return _Rows(
            $isImage ? null : _PageEditorSection(__('cms::cms.colors'), $this->colorsBody(), 'paintbucket'),
            $isImage ? null : _PageEditorSection(__('cms::cms.typography'), $this->typographyBody(), 'text-block'),
            _PageEditorSection(__('cms::cms.spacing'), $this->spacingBody($isImage), 'ruler'),
            !$blockStyles ? null : _PageEditorSection(__('cms::cms.block-options'), $blockStyles, 'setting-2'),
            _PageEditorSection(__('cms::cms.responsive'), $this->responsiveBody(), 'mobile'),
            _PageEditorSection(__('cms::cms.advanced'), $this->advancedBody(), 'code'),
        );
    }

    protected function colorsBody()
    {
        $bgColor = $this->model->getBackgroundColor();
        $isTransparent = $bgColor == 'transparent';

        return _Flex(
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
        )->class('gap-3 !items-start');
    }

    protected function typographyBody()
    {
        return _Rows(
            _Flex(
                _Rows(
                    _Html('cms::cms.font-size')->class('vlStyleSubLabel'),
                    _InputNumber()->name('font-size', false)->default($this->model->getFontSize()),
                )->class('flex-1'),
                _Rows(
                    _Html('cms::cms.text-align')->class('vlStyleSubLabel'),
                    _ButtonGroup()->name('text-align', false)
                        ->default($this->model?->getStyleProperty('text_align') ?: 'center')
                        ->options([
                            'left' => _Html()->icon(_Sax('textalign-left', 16)),
                            'center' => _Html()->icon(_Sax('textalign-center', 16)),
                            'right' => _Html()->icon(_Sax('textalign-right', 16)),
                        ])->optionClass('vlAlignBtn')
                        ->selectedClass('vlAlignBtnActive', 'vlAlignBtnInactive'),
                )->class('flex-1'),
            )->class('gap-3'),
            $this->extraInputs(),
        );
    }

    protected function spacingBody($hidePadding = false)
    {
        return _Tabs(
            _Tab(
                $this->spacingInputs('desktop', $hidePadding),
            )->label('cms::cms.desktop')->class('vlSpacingTabContent'),
            _Tab(
                $this->spacingInputs('mobile', $hidePadding),
            )->label('cms::cms.mobile')->class('vlSpacingTabContent'),
        )->class('vlSpacingTabs');
    }

    protected function spacingInputs($device = 'desktop', $hidePadding = false)
    {
        $suffix = $device === 'mobile' ? '-mobile' : '';
        $defaultVal = $device === 'mobile' ? 0 : null;

        return _Rows(
            $hidePadding ? null : _Html('cms::cms.padding-px')->class('vlStyleSubLabel'),
            $hidePadding ? null : _Div(
                _Input()->placeholder('cms::cms.spacing-top')->name('padding-top' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_top' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-bottom')->name('padding-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-left')->name('padding-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-right')->name('padding-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('padding_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
            )->class('vlSpacingControl vlSpacingPadding'),

            _Html('cms::cms.margin-px')->class($hidePadding ? 'vlStyleSubLabel' : 'vlStyleSubLabel mt-3'),
            _Div(
                _Input()->placeholder('cms::cms.spacing-top')->name('margin-top' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_top' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-bottom')->name('margin-bottom' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_bottom' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-left')->name('margin-left' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_left' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
                _Input()->placeholder('cms::cms.spacing-right')->name('margin-right' . $suffix, false)
                    ->default($this->model?->getStyleProperty('margin_right' . ($suffix ? '_mobile' : '') . '_raw') ?? $defaultVal)
                    ->class('vlSpacingInput whiteField'),
            )->class('vlSpacingControl vlSpacingMargin'),
        );
    }

    protected function responsiveBody()
    {
        return _Rows(
            _Toggle('cms::cms.hide-on-mobile')->name('hide-on-mobile', false)
                ->value((bool) ($this->model?->getStyleProperty('hide_on_mobile_raw') ?? false))
                ->class('vlToggle w-full mb-3'),
            _Toggle('cms::cms.hide-on-desktop')->name('hide-on-desktop', false)
                ->value((bool) ($this->model?->getStyleProperty('hide_on_desktop_raw') ?? false))
                ->class('vlToggle w-full'),
        );
    }

    protected function advancedBody()
    {
        return _Rows(
            _Input('cms::cms.classes')->name('classes')->class('mb-2'),
            _Link('cms::cms.clear-styles')->icon(_Sax('refresh', 14))
                ->selfPost('clearStyles')->inPanel('item_styles_form')
                ->class('vlClearStylesBtn'),
        );
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
