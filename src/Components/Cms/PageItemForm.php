<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageItemForm extends Form
{
    protected $pageId;
    protected $updateOrder;
    public const ITEM_FORM_PANEL_ID = 'itemFormPanel';
    public const ITEM_FORM_STYLES_ID = 'itemFormStyles';
    public const COPY_BLOCK_PANEL_ID = 'copyBlockPanel';

    // Sentinel block_type that means "copy from another page" — not a real item type.
    public const COPY_BLOCK_TYPE = '__copy__';

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
        $types = PageEditor::getOptionsTypes($this->prefixGroup);

        // New block creation — show block type selector
        if (!$this->model->id && !$this->model->block_type) {
            return $this->blockTypeSelector($types);
        }

        // Existing block — show unified property panel
        return $this->unifiedPropertyPanel();
    }

    protected function blockTypeSelector($types)
    {
        if (!$this->model->id) {
            $types = $types + [self::COPY_BLOCK_TYPE => __('cms::cms.copy-block-from-newsletter')];
        }

        return _Rows(
            _Html('cms::cms.add-block')->class('font-semibold text-sm mb-3'),
            _Select('cms::cms.block-type')->options($types)
                ->name('block_type')
                ->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID)
                    && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form')
                    && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID)
                    && $e->selfGet('getCopyBlockPanel')->inPanel(static::COPY_BLOCK_PANEL_ID)
                ),
            !$this->model->id ? _Panel()->id(static::COPY_BLOCK_PANEL_ID)->class('mt-4') : null,
            _Panel(
                $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : null,
            )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
            _Panel(
                $this->getStyleFormComponent(),
            )->id('item_styles_form')->class('mt-2'),
            _Panel()->id(static::ITEM_FORM_STYLES_ID),
            $this->saveButtons(),
        )->class('p-4 vlEditorDrawer');
    }

    protected function unifiedPropertyPanel()
    {
        $blockType = $this->model->getPageItemType();
        $icon = $blockType ? $blockType::ITEM_ICON : 'document-text';
        $title = $blockType ? __($blockType::ITEM_TITLE) : '';

        return _Rows(
            // Block type header with title input
            // Close is handled by the native drawer's own X + click-outside (->inDrawer()).
            _Rows(
                _Flex(
                    _Html()->icon(_Sax($icon, 20))->class('text-blue-600'),
                    _Html($title)->class('font-semibold text-sm'),
                )->class('items-center gap-2 mb-3'),
                _Hidden()->name('block_type')->value($this->model->block_type),
                _Input('cms::cms.title-optional')->name('name_pi'),
            )->class('vlPropertyHeader vlPropertySection mb-4'),

            // Content section
            _Rows(
                $blockType ? $blockType->blockTypeEditorElement() : null,
            )->class('vlPropertySection vlPropertySectionBody mb-4'),

            // Style section (colors, typography, spacing, responsive, advanced — all inside StylePageItemForm)
            _Rows(
                _Html('cms::cms.style')->class('vlPropertySectionTitle'),
                _Rows(
                    _Panel(
                        $this->getStyleFormComponent(),
                    )->id('item_styles_form'),
                    _Panel()->id(static::ITEM_FORM_STYLES_ID),
                )->class('vlPropertySectionBody'),
            )->class('vlPropertySection'),

            // Action buttons
            $this->saveButtons(),
        )->class('vlPropertyPanel vlEditorDrawer');
    }

    protected function saveButtons()
    {
        $previewPanel = PageEditorLayout::PREVIEW_PANEL;

        return _Rows(
            _SubmitButton('cms::cms.save')->class('vlPropertySaveBtn w-full')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel) && $e->closeDrawer())
                ->alert('cms::cms.saved-successfully'),
            $this->model->id ? _DeleteButton('cms::cms.delete-block')
                ->byKey($this->model)
                ->class('vlPropertyDeleteBtn w-full mt-2')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel) && $e->closeDrawer()) : null,
        )->class('vlPropertyActions');
    }

    public function rules()
    {
        $itemRules = !$this->model->block_type ? [] : ($this->model->getPageItemType()?->rules() ?? []);

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
                'panel_id' => PageEditorLayout::PROPERTY_PANEL,
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
        if (!$this->model->getPageItemType()) return;

        $styleModel = PageItemStyleModel::getGenericStylesOfType($this->model->getPageItemType()::class, $this->model->page?->team_id) ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $styleModel->block_type = request('block_type');
        $styleModel->save();
    }

    public function itemForm()
    {
        if(request('block_type') === self::COPY_BLOCK_TYPE || !$this->isValidBlockType()) {
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
            _Html('cms::cms.styles-for-item')->class('text-sm font-semibold mb-1'),
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItemModel::blockTypes()->has($blockType);
    }

    public function getEmptyPropertyState()
    {
        return _Rows(
            _Html()->icon(_Sax('mouse-circle', 48))->class('text-gray-300 mb-4'),
            _Html('cms::cms.select-block-to-edit')->class('text-sm text-gray-400 text-center'),
        )->class('flex flex-col items-center justify-center py-20');
    }

    public function getCopyBlockPanel()
    {
        if (request('block_type') !== self::COPY_BLOCK_TYPE) {
            return _Html('');
        }

        $pages = app(PageBlockService::class)->copyableSourcePages((int) $this->pageId)
            ->mapWithKeys(fn ($page) => [$page->id => $page->title])
            ->toArray();

        return _Rows(
            _Select('cms::cms.select-newsletter')->name('select_newsletter', false)->options($pages)
                ->onChange(fn ($e) => $e->selfGet('getPageBlocksSelect')->inPanel('copyBlockItemsPanel')),
            _Panel(
                _Html(''),
            )->id('copyBlockItemsPanel'),
        );
    }

    public function getPageBlocksSelect()
    {
        $pageId = (int) request('select_newsletter');
        if (!$pageId) {
            return _Html('');
        }

        $options = app(PageBlockService::class)->copyableItemOptions($pageId);

        return _Rows(
            _Select('cms::cms.select-block')->name('select_block', false)->options($options)
                ->onChange(fn ($e) => $e->selfGet('getCopyButton')->inPanel('copyBlockButtonPanel')),
            _Panel(
                _Html(''),
            )->id('copyBlockButtonPanel'),
        );
    }

    public function getCopyButton()
    {
        $itemId = request('select_block');

        if (!$itemId) {
            return _Html('');
        }

        return _Button('cms::cms.copy-this-block')->icon('duplicate')
            ->selfPost('copyBlockToPage', ['item_id' => $itemId])
            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageEditorLayout::PREVIEW_PANEL) && $e->closeDrawer())
            ->class('mt-2');
    }

    public function copyBlockToPage()
    {
        $sourceItem = PageItemModel::findOrFail(request('item_id'));
        $page = PageModel::findOrFail($this->pageId);

        app(PageBlockService::class)->copyToPage($sourceItem, $page);
    }
}
