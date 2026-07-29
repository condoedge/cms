<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Components\Cms\PageEditorLayout;
use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageItemForm extends Form
{
    public $class = 'w-screen max-w-4xl vlPropertyDrawer';

    protected $pageId;
    protected $updateOrder;
    protected $presetCopy;
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

        // When opened from the library's "copy a block" card, land the new-block
        // selector directly on the copy flow (canonical in-drawer copy path).
        $this->presetCopy = (bool) $this->prop('preset_copy');

        $this->pageId = $this->prop('page_id');
        $this->model->page_id = $this->pageId;

        $this->model->block_type = $this->model->block_type ?: request('block_type');
    }

    public function beforeSave()
    {
        if ($this->updateOrder) {
            $this->model->order = $this->model->page->pageItems()->count() - 1;
        }

        static::applyRequestContentToModel($this->model);
    }

    public function afterSave()
    {
        $styleModel = $this->model->styles ?? PageItemStyleModel::make();

        static::applyRequestStylesToModel($styleModel);

        $this->model->styles()->save($styleModel);
    }

    /**
     * Shared save-time request() -> model mapping, split so both the real save
     * path (beforeSave/afterSave) and the live-preview hydration call the exact
     * piece they need and can never drift. READ-ONLY: neither persists; callers
     * decide whether to save. Static so PagePreview can reuse them without a Form.
     */
    protected static function applyRequestContentToModel($item)
    {
        $item->title = request('title');
        $item->content = request('content');
    }

    protected static function applyRequestStylesToModel($styleModel)
    {
        PageStyle::setStylesToModel($styleModel);

        $styleModel->content .= request('styles');
    }

    /* LIVE PREVIEW (read-only — never persists) */

    /**
     * Build an in-memory PageItem carrying $savedItem's id/page_id/block_type
     * plus the unsaved request() edits, with a transient style attached in
     * memory. NEVER calls save() — this exists purely to render a preview of
     * what the operator is currently typing. Shared by PageItemForm and
     * PagePreview (the full-page tab) so both hydrate identically.
     *
     * @param  mixed|null  $savedItem  The persisted model being edited (null for a new block).
     * @param  int|null    $pageId
     */
    public static function hydratePendingItem($savedItem, $pageId)
    {
        $item = PageItemModel::make();

        // Carry identity so the type's constructor and the page-context swap
        // can locate/relate the block, without touching the DB.
        if ($savedItem?->id) {
            $item->id = $savedItem->id;
            $item->exists = true;
            $item->setRelation('page', $savedItem->page);

            // Preserve already-saved image attributes (text-only edits don't
            // resubmit them); mirrors the type beforeSave preserve behavior.
            foreach (['image', 'image_preview'] as $attr) {
                if (!empty($savedItem->getAttribute($attr))) {
                    $item->setAttribute($attr, $savedItem->getAttribute($attr));
                }
            }
        }

        $item->page_id = $pageId;
        $item->block_type = $savedItem?->block_type ?: request('block_type');

        $styleModel = PageItemStyleModel::make();
        // Seed from the saved style so request-driven overrides merge onto the
        // existing block style (same starting point as the real afterSave).
        $styleModel->content = (string) ($savedItem?->styles?->content ?? '');

        static::applyRequestContentToModel($item);

        // Non-persisting per-type preview derivations (e.g. BoxedContent resolves
        // its preset-color into concrete colors via request()->merge). MUST run
        // before applyRequestStylesToModel so setStylesToModel reads the merged
        // values from request(). The real save path does this via afterSave.
        $item->getPageItemType()?->applyPreviewMapping();

        static::applyRequestStylesToModel($styleModel);

        // Attach the transient style in memory only.
        $item->setRelation('styles', $styleModel);

        return $item;
    }

    /**
     * Initial (drawer-open) panel content: render the SAVED block. The open
     * request carries no form-field values, so hydrating from request() here
     * would strip styles and blank the content; previewPendingBlock (fed by
     * withAllFormValues) takes over from the first edit. Defensive render.
     */
    protected function savedBlockPreview()
    {
        try {
            $element = $this->model->id
                ? $this->model->getPageItemType()?->toPreviewElement(false)
                : null;
        } catch (\Throwable $e) {
            $element = null;
        }

        return $element ?? _Div(
            _Html('cms::cms.preview-unavailable')->class('vlLivePreviewHint'),
        )->class('vlLivePreviewEmpty');
    }

    /**
     * "This block" tab: render only the edited block from unsaved request values.
     * Defensive (try/catch) so a half-typed value never errors the panel —
     * mirrors toPreviewSinglePageItem.
     */
    public function previewPendingBlock()
    {
        try {
            $item = static::hydratePendingItem($this->model->id ? $this->model : null, $this->pageId);
            $element = $item->getPageItemType()?->toPreviewElement(false);
        } catch (\Throwable $e) {
            $element = null;
        }

        return $element ?? _Div(
            _Html('cms::cms.preview-unavailable')->class('vlLivePreviewHint'),
        )->class('vlLivePreviewEmpty');
    }

    /**
     * "Full page" tab: render the saved page through the existing PagePreview,
     * but swap the edited block for the hydrated (unsaved) transient model via
     * PagePreview's pending-override prop. New blocks (no id) are appended.
     */
    public function previewPendingPage()
    {
        return PageEditor::getPagePreviewComponent(
            $this->prefixGroup,
            [
                'page_id' => $this->pageId,
                'with_editor' => false,
                // Render inside the property drawer's fixed-width column: suppress
                // the (globally-scoped) device toggle and the exterior background.
                'in_drawer' => true,
                // Tell PagePreview which saved row to swap for the unsaved
                // request edits. 'new' means: append the pending block.
                'pending_item_id' => $this->model->id ?: 'new',
            ]
        );
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
                ->value($this->presetCopy ? self::COPY_BLOCK_TYPE : null)
                ->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID)
                    && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form')
                    && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID)
                    && $e->selfGet('getCopyBlockPanel')->inPanel(static::COPY_BLOCK_PANEL_ID)
                ),
            !$this->model->id ? _Panel(
                $this->presetCopy ? $this->renderCopyBlockPanel() : null,
            )->id(static::COPY_BLOCK_PANEL_ID)->class('mt-4') : null,
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
        // Two columns: live preview on the left (the drawer overlay hides the
        // canvas), the property form on the right. The hidden trigger + tab
        // field live inside the form so withAllFormValues() carries them.
        return _Flex(
            $this->livePreviewColumn(),
            _Rows(
                $this->propertyFormColumn(),
            )->class('vlPropertyFormColumn'),
        )->class('vlPropertyLayout items-stretch');
    }

    /**
     * Left column — tab switcher [This block][Full page] + the live-preview
     * panel on a mini-canvas surface. Each tab button selfGets its own render
     * method into the panel; the JS bridge re-clicks the active tab as fields
     * change to keep the preview live.
     */
    protected function livePreviewColumn()
    {
        return _Rows(
            _Flex(
                $this->previewTabButton('block', 'cms::cms.this-block', 'previewPendingBlock'),
                $this->previewTabButton('page', 'cms::cms.full-page', 'previewPendingPage'),
            )->class('vlLivePreviewTabs'),

            _Div(
                // On open, render the SAVED block (the drawer-open request has no
                // form values, so request-hydrating here would blank it). Each tab
                // button refreshes this panel via its own selfGet; the JS bridge
                // re-clicks the active tab as fields change.
                _Panel(
                    $this->savedBlockPreview(),
                )->id('live-preview')->class('vlLivePreview'),
            )->class('vlLivePreviewCanvas'),
        )->class('vlLivePreviewColumn');
    }

    protected function previewTabButton($tab, $label, $method)
    {
        $active = $tab === 'block';

        // Each tab directly selfGets its own render method — there is no shared
        // hidden field (its DOM value never reliably reached the server's Vue
        // model, so the "Full page" tab always fell back to the block render).
        // withAllFormValues carries the unsaved edits so the preview reflects
        // what's being typed. The run() only sets the cosmetic active-tab class;
        // Kompo's run() passes a ctx whose vue.$el is this button (used to scope
        // to its own drawer, defensive against future drawer stacking).
        return _Button($label)
            ->class('vlLivePreviewTab')
            ->class($active ? 'vlLivePreviewTabActive' : '')
            ->attr(['data-preview-tab' => $tab])
            ->selfGet($method)->withAllFormValues()->inPanel('live-preview')
            ->run("(ctx) => {
                var btn = ctx && ctx.vue && ctx.vue.\$el;
                var drawer = btn && btn.closest('.vlPropertyDrawer');
                if (!drawer) return;
                drawer.querySelectorAll('.vlLivePreviewTab').forEach(function (t) { t.classList.remove('vlLivePreviewTabActive'); });
                btn.classList.add('vlLivePreviewTabActive');
            }");
    }

    /**
     * Right column — the original unified property form (unchanged behavior).
     */
    protected function propertyFormColumn()
    {
        $blockType = $this->model->getPageItemType();
        $icon = $blockType ? $blockType::ITEM_ICON : 'document-text';
        $title = $blockType ? __($blockType::ITEM_TITLE) : '';

        return _Rows(
            // Block type header with title input
            // Close is handled by the native drawer's own X + click-outside (->inDrawer()).
            _Rows(
                _FlexBetween(
                    _Flex(
                        _Html()->icon(_Sax($icon, 20))->class('text-blue-600'),
                        _Html($title)->class('font-semibold text-sm'),
                    )->class('items-center gap-2'),
                )->class('mb-3'),
                _Hidden()->name('block_type')->value($this->model->block_type),
                _Input('cms::cms.title-optional')->name('name_pi'),
            )->class('vlPropertyHeader vlPropertySection mb-4'),

            // Per-card settings (block sub-items — Group blocks only): each one its own white card.
            $blockType ? _Rows($blockType->blockTypeEditorElement())->class('vlPropertySection mb-4') : null,

            // General settings (block-wide style: colors, typography, spacing, responsive, advanced).
            // Visually offset on a gray surface with a section label so the operator can tell at a
            // glance that these apply to the whole block, not to any one sub-item.
            _Rows(
                _Flex(
                    _Html('cms::cms.general-settings')
                        ->class('text-xs font-semibold uppercase tracking-wider text-gray-500'),
                    _Html()->class('flex-1 border-t border-gray-200'),
                )->class('items-center gap-3 mb-3'),
                _Rows(
                    _Panel($this->getStyleFormComponent())->id('item_styles_form'),
                    _Panel()->id(static::ITEM_FORM_STYLES_ID),
                )->class('bg-gray-100 rounded-xl p-3'),
            )->class('vlPropertySection mb-4'),

            // Action buttons
            $this->saveButtons(),
        )->class('vlPropertyPanel vlEditorDrawer');
    }

    protected function saveButtons()
    {
        $previewPanel = PageEditorLayout::PREVIEW_PANEL;

        return _Rows(
            _SubmitButton('cms::cms.save')->class('vlPropertySaveBtn w-full')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel)->closeDrawer())
                ->alert('cms::cms.saved-successfully'),
            $this->model->id ? _DeleteButton('cms::cms.delete-block')
                ->byKey($this->model)
                ->class('vlPropertyDeleteBtn w-full mt-2')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel)->closeDrawer()) : null,
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

    public function getCopyBlockPanel()
    {
        if (request('block_type') !== self::COPY_BLOCK_TYPE) {
            return _Html('');
        }

        return $this->renderCopyBlockPanel();
    }

    protected function renderCopyBlockPanel()
    {
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
            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageEditorLayout::PREVIEW_PANEL)->closeDrawer())
            ->class('mt-2');
    }

    public function copyBlockToPage()
    {
        $sourceItem = PageItemModel::findOrFail(request('item_id'));
        $page = PageModel::findOrFail($this->pageId);

        app(PageBlockService::class)->copyToPage($sourceItem, $page);
    }
}
