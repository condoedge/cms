<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class EmailEditorLayout extends Form
{
    public $id = 'email-editor-layout';
    protected $prefixGroup = "";

    public const PREVIEW_PANEL = 'email-editor-preview-panel';
    public const PROPERTY_PANEL = 'email-editor-property-panel';
    public const BLOCK_LIBRARY_PANEL = 'email-editor-block-library-panel';

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        if (!$this->model->id) {
            return $this->emptyState();
        }

        return _Rows(
            _Html('<a href="#'.static::PREVIEW_PANEL.'" class="vlSkipLink">'.__('cms::cms.skip-to-canvas').'</a>'),
            $this->editorStyles(),
            $this->topBar(),
            $this->editorBody(),
            $this->editorJs(),
        )->class('vlEmailEditorWrapper')->attr(['role' => 'application', 'aria-label' => __('cms::cms.email-editor')]);
    }

    protected function emptyState()
    {
        return _Rows(
            _Html('cms::cms.first-save-page')->class('text-xl text-gray-500 text-center p-12'),
        );
    }

    protected function topBar()
    {
        return new EditorTopBar($this->model->id, [
            'prefix_group' => $this->prefixGroup,
        ]);
    }

    protected function editorBody()
    {
        return _Div(
            $this->leftPanel(),
            $this->centerPanel(),
            _Html('<div class="vlDrawerBackdrop" onclick="vlEmailEditor.closeDrawer()"></div>'),
            $this->rightPanel(),
        )->class('vlEditorBody');
    }

    protected function leftPanel()
    {
        return _Div(
            _Panel(
                new BlockLibraryPanel(null, [
                    'page_id' => $this->model->id,
                    'prefix_group' => $this->prefixGroup,
                ]),
            )->id(static::BLOCK_LIBRARY_PANEL),
        )->class('vlEditorLeftPanel')->attr(['role' => 'complementary', 'aria-label' => __('cms::cms.blocks')]);
    }

    protected function centerPanel()
    {
        return _Div(
            _Div(
                _Panel(
                    PageEditor::getPagePreviewComponent($this->prefixGroup, [
                        'page_id' => $this->model->id,
                        'panel_id' => static::PROPERTY_PANEL,
                        'with_editor' => true,
                    ]),
                )->id(static::PREVIEW_PANEL),
            )->class('vlCanvasFrame'),
        )->class('vlEditorCenterPanel')->attr(['role' => 'main', 'aria-label' => __('cms::cms.canvas')]);
    }

    protected function rightPanel()
    {
        return _Div(
            _Flex(
                _Html('cms::cms.block-properties')->class('vlDrawerTitle'),
                _Link()->icon('x')->class('vlDrawerClose')
                    ->run('() => { vlEmailEditor.closeDrawer() }'),
            )->class('vlDrawerHeader'),
            _Panel()->id(static::PROPERTY_PANEL),
        )->class('vlEditorRightPanel')->attr(['role' => 'complementary', 'aria-label' => __('cms::cms.block-properties')]);
    }

    protected function editorStyles()
    {
        $bgColor = $this->model->getExteriorBackgroundColor();
        $contentBg = $this->model->getContentBackgroundColor();
        $maxWidth = $this->model->getContentMaxWidth();

        return _Html('<style>
            .vlEmailEditorWrapper {
                display: flex;
                flex-direction: column;
                height: 100vh;
                overflow: hidden;
                background: #f3f4f6;
            }

            /* Top Bar */
            .vlEditorTopBar {
                height: 56px;
                min-height: 56px;
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
                padding: 0 16px;
                display: flex;
                align-items: center;
                z-index: 50;
            }
            .vlEditorTopBarBack {
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                color: #6b7280;
                transition: all 0.15s;
            }
            .vlEditorTopBarBack:hover {
                background: #f3f4f6;
                color: #111827;
            }
            .vlEditorTopBarTitle {
                min-width: 200px;
            }
            .vlEditorTopBarTitle:hover {
                background: #f3f4f6 !important;
            }
            .vlEditorTopBarTitle:focus {
                background: #ffffff !important;
                box-shadow: 0 0 0 2px #3b82f6 !important;
            }

            /* Device Toggles */
            .vlDeviceToggleGroup {
                display: flex;
                background: #f3f4f6;
                border-radius: 8px;
                padding: 5px 3px;
                gap: 2px;
            }
            .vlDeviceToggle {
                width: 36px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #9ca3af;
                transition: all 0.15s;
                cursor: pointer;
            }
            .vlDeviceToggle:hover {
                color: #6b7280;
            }
            .vlDeviceToggleActive {
                background: #ffffff;
                color: #111827 !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            }

            /* Action Buttons */
            .vlEditorActionBtn {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 10px 16px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                transition: all 0.15s;
                white-space: nowrap;
            }
            .vlEditorActionBtn:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }
            .vlEditorPreviewBtn {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 10px 16px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                transition: all 0.15s;
            }
            .vlEditorPreviewBtn:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }
            .vlEditorSaveBtn {
                padding: 10px 20px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                border: none !important;
                transition: all 0.15s !important;
            }
            .vlEditorSaveBtn:hover {
                background: #1d4ed8 !important;
            }

            /* Editor Body - 3 Panel Layout */
            .vlEditorBody {
                display: flex;
                flex: 1;
                overflow: hidden;
            }

            /* Left Panel */
            .vlEditorLeftPanel {
                width: 300px;
                min-width: 300px;
                background: #ffffff;
                border-right: 1px solid #e5e7eb;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Block Library */
            .vlBlockLibPanel {
                height: 100%;
            }
            .vlBlockLibTabs > ul {
                gap: 16px;
                padding: 0 24px;
            }
            .vlBlockLibTabs > ul > li > a {
                font-size: 13px;
                font-weight: 600;
                padding: 12px 0;
            }
            .vlBlockLibTabContent {
                padding: 0;
            }
            .vlBlockList {
                padding: 24px;
            }
            .vlBlockCategoryLabel {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #9ca3af;
                padding: 16px 4px 8px;
            }
            .vlBlockCategoryLabel:first-child {
                padding-top: 4px;
            }
            .vlBlockGrid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 4px;
            }
            .vlBlockCard {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 14px 8px !important;
                background: #f9fafb;
                border: 1.5px solid #e5e7eb !important;
                border-radius: 10px !important;
                cursor: pointer;
                transition: all 0.15s;
                min-height: 80px;
                text-decoration: none !important;
                gap: 6px;
            }
            .vlBlockCard:hover {
                border-color: #93c5fd !important;
                background: #eff6ff;
                box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
                transform: translateY(-1px);
            }
            .vlBlockCard:active {
                transform: translateY(0);
            }
            /* Icon inside the Link */
            .vlBlockCard .vlIcon,
            .vlBlockCard > div:first-child {
                color: #6b7280;
                margin: 0 !important;
                order: -1;
            }
            .vlBlockCard:hover .vlIcon,
            .vlBlockCard:hover > div:first-child {
                color: #2563eb;
            }
            /* Label text inside the Link */
            .vlBlockCard,
            .vlBlockCard .vlLabel {
                font-size: 11px !important;
                font-weight: 500 !important;
                color: #111827 !important;
                text-align: center !important;
                line-height: 1.2;
            }
            .vlBlockCardCopy {
                grid-column: 1 / -1;
                flex-direction: row !important;
                gap: 8px !important;
                min-height: auto;
                padding: 10px 14px !important;
                margin-top: 8px;
            }
            .vlBlockCardCopy .vlLabel {
                font-size: 12px !important;
            }

            /* Center Panel - Canvas */
            .vlEditorCenterPanel {
                flex: 1;
                overflow-y: auto;
                overflow-x: hidden;
                background: '.$bgColor.';
                padding: 32px;
            }
            .vlCanvasFrame {
                max-width: '.$maxWidth.'px;
                margin: 0 auto;
                background: '.$contentBg.';
                min-height: 400px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 24px rgba(0,0,0,0.04);
                border-radius: 4px;
                transition: max-width 0.3s ease;
            }

            /* Mobile Preview Frame */
            .vlCanvasFrame.vlMobilePreview {
                max-width: 375px;
                min-height: 812px;
                border-radius: 32px;
                border: 8px solid #1f2937;
                padding: 20px 0 20px;
                background: '.$contentBg.';
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
                position: relative;
                overflow-y: auto;
            }
            .vlCanvasFrame.vlMobilePreview::before {
                content: "";
                display: block;
                width: 40px;
                height: 4px;
                background: #374151;
                border-radius: 2px;
                margin: 0 auto 12px;
            }

            /* Right Panel - Drawer */
            .vlEditorRightPanel {
                position: fixed;
                top: 0;
                right: 0;
                width: 560px;
                height: 100vh;
                background: #ffffff;
                border-left: 1px solid #e5e7eb;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 60;
                transform: translateX(100%);
                transition: transform 0.25s ease;
                box-shadow: none;
            }
            .vlEditorRightPanel.vlDrawerOpen {
                transform: translateX(0);
                box-shadow: -8px 0 30px rgba(0,0,0,0.12);
            }
            .vlDrawerBackdrop {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.15);
                z-index: 55;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }
            .vlDrawerBackdrop.vlDrawerBackdropVisible {
                opacity: 1;
                pointer-events: auto;
            }
            .vlDrawerHeader {
                position: sticky;
                top: 0;
                background: #ffffff;
                border-bottom: 1px solid #f3f4f6;
                padding: 14px 32px;
                z-index: 5;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .vlDrawerTitle {
                font-size: 14px;
                font-weight: 600;
                color: #111827;
            }
            .vlDrawerClose {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #9ca3af;
                cursor: pointer;
                transition: all 0.15s;
            }
            .vlDrawerClose:hover {
                background: #f3f4f6;
                color: #111827;
            }

            /* Empty block placeholder (from PHP for truly null blocks) */
            .vlEmptyBlockPlaceholder {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 72px;
                background: #f9fafb;
                border: 1.5px dashed #d1d5db;
                border-radius: 6px;
                color: #9ca3af;
                font-size: 13px;
                padding: 12px;
                margin: 4px;
            }

            /* JS-detected empty block overlay */
            .vlEmailBlock.vlEmailBlockEmpty::after {
                content: attr(data-block-type) " — '.__('cms::cms.click-to-edit').'";
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 72px;
                background: #f9fafb;
                border: 1.5px dashed #d1d5db;
                border-radius: 6px;
                color: #9ca3af;
                font-size: 13px;
                padding: 12px;
                margin: 4px;
            }

            /* Block selection & hover in canvas */
            .vlEmailBlock {
                border: 2px solid transparent;
                border-radius: 4px;
                transition: border-color 0.15s, box-shadow 0.15s;
                cursor: pointer;
                position: relative;
                min-height: 48px;
            }
            .vlEmailBlock:hover {
                border-color: #93c5fd;
            }
            .vlEmailBlock:hover .vlEmptyBlockPlaceholder,
            .vlEmailBlock.vlEmailBlockEmpty:hover::after {
                border-color: #93c5fd;
                background: #eff6ff;
                color: #6b7280;
            }
            .vlEmailBlock.vlEmailBlockSelected {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 1px #2563eb;
            }

            /* Block action toolbar */
            .vlBlockActions {
                display: none;
                position: absolute;
                top: 4px;
                right: 4px;
                z-index: 10;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 4px;
                gap: 2px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .vlEmailBlock:hover .vlBlockActions,
            .vlEmailBlock.vlEmailBlockSelected .vlBlockActions {
                display: flex;
            }
            .vlBlockActionBtn {
                width: 30px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #6b7280;
                cursor: pointer;
                transition: all 0.1s;
                font-size: 14px;
            }
            .vlBlockActionBtn:hover {
                background: #f3f4f6;
                color: #111827;
            }
            .vlBlockActionBtn.vlBlockActionBtnDanger:hover {
                background: #fef2f2;
                color: #dc2626;
            }
            .vlBlockDragHandle {
                cursor: grab;
            }
            .vlBlockDragHandle:active {
                cursor: grabbing;
            }
            .vlBlockTypeLabel {
                font-size: 11px;
                font-weight: 500;
                color: #9ca3af;
                padding: 0 6px;
                white-space: nowrap;
            }

            /* Toast notification */
            .vlEditorToast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #065f46;
                color: #ffffff;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 500;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                z-index: 9999;
                animation: vlToastIn 0.2s ease, vlToastOut 0.2s ease 2s forwards;
            }
            @keyframes vlToastIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes vlToastOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(8px); }
            }

            /* Empty canvas state */
            .vlEmptyCanvas {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 80px 40px;
                border: 2px dashed #d1d5db;
                border-radius: 12px;
                margin: 24px;
            }
            .vlEmptyCanvas svg, .vlEmptyCanvas i {
                color: #d1d5db;
                margin-bottom: 16px;
            }

            /* Sortable placeholder for drag reorder */
            .vlSortablePlaceholder {
                height: 4px;
                background: #2563eb;
                border-radius: 2px;
                margin: 6px 8px;
                opacity: 0.8;
            }

            /* Block animations */
            .vlEmailBlock {
                animation: vlBlockFadeIn 0.2s ease;
            }
            @keyframes vlBlockFadeIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .vlBlockRemoving {
                animation: vlBlockFadeOut 0.2s ease forwards;
                pointer-events: none;
            }
            @keyframes vlBlockFadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.96); }
            }

            /* Undo/Redo bar */
            .vlUndoRedoGroup {
                display: flex;
                background: #f3f4f6;
                border-radius: 8px;
                padding: 3px;
                gap: 2px;
            }
            .vlUndoRedoBtn {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #6b7280;
                cursor: pointer;
                transition: all 0.15s;
            }
            .vlUndoRedoBtn:hover:not(.vlUndoRedoBtnDisabled) {
                background: #ffffff;
                color: #111827;
                box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            }
            .vlUndoRedoBtnDisabled {
                color: #d1d5db !important;
                cursor: default;
                pointer-events: none;
            }

            /* Loading overlay for panels */
            .vlPanelLoading {
                position: relative;
            }
            .vlPanelLoading::after {
                content: "";
                position: absolute;
                inset: 0;
                background: rgba(255,255,255,0.7);
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Responsive editor — small screens */
            @media (max-width: 1100px) {
                .vlEditorLeftPanel {
                    width: 220px;
                    min-width: 220px;
                }
            }
            @media (max-width: 900px) {
                .vlEditorBody {
                    flex-direction: column;
                    position: relative;
                }
                .vlEditorLeftPanel {
                    width: 100%;
                    min-width: 100%;
                    max-height: 50vh;
                    border-right: none;
                    border-bottom: 1px solid #e5e7eb;
                    display: none;
                }
                .vlEditorLeftPanel.vlPanelMobileOpen {
                    display: block;
                }
                .vlEditorCenterPanel {
                    padding: 16px;
                }
                .vlEditorRightPanel {
                    width: 100%;
                }
                .vlMobilePanelToggle {
                    display: flex !important;
                }
            }
            @media (min-width: 901px) {
                .vlMobilePanelToggle {
                    display: none !important;
                }
            }

            /* Focus visible for accessibility */
            .vlBlockCard:focus-visible,
            .vlBlockActionBtn:focus-visible,
            .vlDeviceToggle:focus-visible,
            .vlEditorActionBtn:focus-visible {
                outline: 2px solid #2563eb;
                outline-offset: 2px;
            }

            /* Skip to content link (accessibility) */
            .vlSkipLink {
                position: absolute;
                top: -40px;
                left: 0;
                background: #2563eb;
                color: #ffffff;
                padding: 8px 16px;
                z-index: 200;
                font-size: 13px;
                font-weight: 600;
                border-radius: 0 0 8px 0;
                transition: top 0.2s;
            }
            .vlSkipLink:focus {
                top: 0;
            }

            /* Actions Dropdown Menu */
            .vlActionsMenu {
                position: absolute;
                top: 48px;
                right: 16px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 6px;
                min-width: 200px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.12);
                z-index: 100;
                animation: vlDropdownIn 0.1s ease;
            }
            @keyframes vlDropdownIn {
                from { opacity: 0; transform: translateY(-4px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .vlActionsMenuItem {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                border-radius: 6px;
                transition: background 0.1s;
                white-space: nowrap;
                width: 100%;
            }
            .vlActionsMenuItem:hover {
                background: #f3f4f6;
            }
            .vlActionsMenuDivider {
                height: 1px;
                background: #f3f4f6;
                margin: 4px 0;
            }

            /* Block Search */
            .vlBlockSearchInput {
                font-size: 14px !important;
                padding: 12px 10px 12px 32px !important;
                border-radius: 8px !important;
                border: 1px solid #e5e7eb !important;
                background: #f9fafb !important;
                width: 100%;
            }
            .vlBlockSearchInput:focus {
                background: #ffffff !important;
                border-color: #93c5fd !important;
                box-shadow: 0 0 0 2px rgba(59,130,246,0.08) !important;
            }
            .vlBlockSearchWrap {
                position: relative;
            }
            .vlBlockSearchIcon {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: #9ca3af;
                pointer-events: none;
                z-index: 1;
            }

            /* Keyboard shortcut hint */
            .vlShortcutHint {
                position: fixed;
                bottom: 24px;
                left: 24px;
                background: #1f2937;
                color: #d1d5db;
                padding: 8px 14px;
                border-radius: 8px;
                font-size: 12px;
                z-index: 9998;
                display: flex;
                gap: 16px;
            }
            .vlShortcutHint kbd {
                background: #374151;
                padding: 1px 5px;
                border-radius: 3px;
                font-size: 11px;
                font-family: inherit;
                color: #e5e7eb;
            }

            /* Override kompo defaults inside editor */
            .vlEditorRightPanel .vlCard { box-shadow: none !important; border: none !important; }
            .vlEditorLeftPanel .vlTabPaneContent { padding: 0 !important; }
        </style>');
    }

    protected function editorJsCode()
    {
        $undoLabel = __('cms::cms.undo');
        $redoLabel = __('cms::cms.redo');
        $nothingToUndo = __('cms::cms.nothing-to-undo');
        $nothingToRedo = __('cms::cms.nothing-to-redo');
        $propertyPanel = static::PROPERTY_PANEL;

        return <<<JS
(function() {
    if (window.vlEmailEditor) return;

    window.vlEmailEditor = {
        _actionsMenuOpen: false,
        _undoStack: [],
        _redoStack: [],

        setDevice: function(device) {
            var frame = document.querySelector(".vlCanvasFrame");
            var toggles = document.querySelectorAll(".vlDeviceToggle");
            toggles.forEach(function(t) { t.classList.remove("vlDeviceToggleActive"); });
            var activeToggle = document.querySelector("[data-device='" + device + "']");
            if (activeToggle) activeToggle.classList.add("vlDeviceToggleActive");
            if (!frame) return;
            if (device === "mobile") { frame.classList.add("vlMobilePreview"); }
            else { frame.classList.remove("vlMobilePreview"); }
        },

        selectBlock: function(blockEl) {
            document.querySelectorAll(".vlEmailBlock").forEach(function(b) {
                b.classList.remove("vlEmailBlockSelected");
            });
            if (blockEl) {
                blockEl.classList.add("vlEmailBlockSelected");
                this.openDrawer();
            }
        },

        getSelectedBlock: function() {
            return document.querySelector(".vlEmailBlockSelected");
        },

        showToast: function(message) {
            var existing = document.querySelector(".vlEditorToast");
            if (existing) existing.remove();
            var toast = document.createElement("div");
            toast.className = "vlEditorToast";
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 2500);
        },

        toggleActionsMenu: function(triggerEl) {
            var existing = document.querySelector(".vlActionsMenu");
            if (existing) { existing.remove(); this._actionsMenuOpen = false; return; }
            if (triggerEl) triggerEl.click();
            this._actionsMenuOpen = true;
        },

        closeActionsMenu: function() {
            var existing = document.querySelector(".vlActionsMenu");
            if (existing) existing.remove();
            this._actionsMenuOpen = false;
        },

        filterBlocks: function(query) {
            var cards = document.querySelectorAll(".vlBlockCard:not(.vlBlockCardCopy)");
            var categories = document.querySelectorAll(".vlBlockCategoryLabel");
            var grids = document.querySelectorAll(".vlBlockGrid");
            var q = query.toLowerCase().trim();
            cards.forEach(function(card) {
                var label = card.querySelector(".vlBlockCardLabel, .vlLabel");
                var text = label ? label.textContent.toLowerCase() : card.textContent.toLowerCase();
                card.style.display = (!q || text.indexOf(q) !== -1) ? "" : "none";
            });
            grids.forEach(function(grid, i) {
                var visibleCards = grid.querySelectorAll('.vlBlockCard:not([style*="display: none"])');
                var catLabel = categories[i];
                if (catLabel) catLabel.style.display = visibleCards.length > 0 ? "" : "none";
                grid.style.display = visibleCards.length > 0 ? "" : "none";
            });
        },

        snapshotBlockOrder: function() {
            var blocks = document.querySelectorAll(".vlEmailBlock[data-block-id]");
            var ids = [];
            blocks.forEach(function(b) { ids.push(b.getAttribute("data-block-id")); });
            return ids;
        },

        pushUndo: function(action) {
            this._undoStack.push(action);
            if (this._undoStack.length > 30) this._undoStack.shift();
            this._redoStack = [];
            this.updateUndoRedoUI();
        },

        undo: function() {
            if (this._undoStack.length === 0) return;
            var action = this._undoStack.pop();
            this._redoStack.push(action);
            this.updateUndoRedoUI();
            this.showToast("{$undoLabel}: " + action.label);
            if (action.undoFn) action.undoFn();
        },

        redo: function() {
            if (this._redoStack.length === 0) return;
            var action = this._redoStack.pop();
            this._undoStack.push(action);
            this.updateUndoRedoUI();
            this.showToast("{$redoLabel}: " + action.label);
            if (action.redoFn) action.redoFn();
        },

        updateUndoRedoUI: function() {
            var undoBtn = document.querySelector("[data-undo-btn]");
            var redoBtn = document.querySelector("[data-redo-btn]");
            if (undoBtn) {
                undoBtn.classList.toggle("vlUndoRedoBtnDisabled", this._undoStack.length === 0);
                undoBtn.title = this._undoStack.length > 0 ? ("{$undoLabel} " + this._undoStack[this._undoStack.length-1].label) : "{$nothingToUndo}";
            }
            if (redoBtn) {
                redoBtn.classList.toggle("vlUndoRedoBtnDisabled", this._redoStack.length === 0);
                redoBtn.title = this._redoStack.length > 0 ? ("{$redoLabel} " + this._redoStack[this._redoStack.length-1].label) : "{$nothingToRedo}";
            }
        },

        refreshPreview: function() {
            var wrapper = document.querySelector(".vlQueryWrapperPagePreview");
            if (!wrapper) { window.location.reload(); return; }
            var vm = wrapper.__vue__;
            while (vm && !vm.browseQuery) { vm = vm.\$parent; }
            if (vm && vm.browseQuery) { vm.browseQuery(); }
            else { window.location.reload(); }
        },

        openDrawer: function() {
            var panel = document.querySelector(".vlEditorRightPanel");
            var backdrop = document.querySelector(".vlDrawerBackdrop");
            if (panel) panel.classList.add("vlDrawerOpen");
            if (backdrop) backdrop.classList.add("vlDrawerBackdropVisible");
        },

        closeDrawer: function() {
            var panel = document.querySelector(".vlEditorRightPanel");
            var backdrop = document.querySelector(".vlDrawerBackdrop");
            if (panel) panel.classList.remove("vlDrawerOpen");
            if (backdrop) backdrop.classList.remove("vlDrawerBackdropVisible");
            document.querySelectorAll(".vlEmailBlock").forEach(function(b) {
                b.classList.remove("vlEmailBlockSelected");
            });
        },

        waitAndClickBlock: function(blockId, attempts) {
            attempts = attempts || 0;
            if (attempts > 20) return;
            var block = document.querySelector('.vlEmailBlock[data-block-id="' + blockId + '"]');
            if (block) {
                block.click();
                block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                sessionStorage.removeItem('vlPendingBlockId');
            } else {
                setTimeout(function() { vlEmailEditor.waitAndClickBlock(blockId, attempts + 1); }, 300);
            }
        },

        toggleMobilePanel: function(panel) {
            var leftPanel = document.querySelector(".vlEditorLeftPanel");
            if (panel === "blocks") { leftPanel.classList.toggle("vlPanelMobileOpen"); }
            else if (panel === "properties") { this.openDrawer(); }
        }
    };

    // Close actions menu on outside click
    document.addEventListener("click", function(e) {
        if (vlEmailEditor._actionsMenuOpen && !e.target.closest(".vlActionsMenu") && !e.target.closest("[data-actions-trigger]")) {
            vlEmailEditor.closeActionsMenu();
        }
    });

    // Open drawer on block click (capture phase), but not on action buttons
    document.addEventListener("click", function(e) {
        if (e.target.closest(".vlBlockActions")) return;
        var block = e.target.closest(".vlEmailBlock");
        if (block) { vlEmailEditor.selectBlock(block); }
    }, true);

    // Keyboard shortcuts
    document.addEventListener("keydown", function(e) {
        var tag = e.target.tagName.toLowerCase();
        var isInput = tag === "input" || tag === "textarea" || tag === "select" || e.target.isContentEditable;

        if ((e.ctrlKey || e.metaKey) && e.key === "s") {
            e.preventDefault();
            var saveBtn = document.querySelector(".vlEditorSaveBtn");
            if (saveBtn) saveBtn.click();
            return;
        }
        if ((e.ctrlKey || e.metaKey) && e.key === "z" && !e.shiftKey) { e.preventDefault(); vlEmailEditor.undo(); return; }
        if ((e.ctrlKey || e.metaKey) && e.key === "z" && e.shiftKey) { e.preventDefault(); vlEmailEditor.redo(); return; }
        if (isInput) return;
        if (e.key === "Escape") {
            var modal = document.querySelector(".vlTestEmailModal, .vlSaveTemplateModal, .vlTemplateModal");
            if (modal) { modal.remove(); return; }
            vlEmailEditor.closeActionsMenu();
            vlEmailEditor.closeDrawer();
            return;
        }
        if (e.key === "Delete" || e.key === "Backspace") {
            var selected = vlEmailEditor.getSelectedBlock();
            if (selected) {
                var deleteBtn = selected.querySelector(".vlBlockActionBtnDanger");
                if (deleteBtn) { e.preventDefault(); deleteBtn.click(); }
            }
        }
        if (e.key === "ArrowUp" || e.key === "ArrowDown") {
            var selected = vlEmailEditor.getSelectedBlock();
            if (selected) {
                e.preventDefault();
                var blocks = Array.from(document.querySelectorAll(".vlEmailBlock"));
                var idx = blocks.indexOf(selected);
                var next = e.key === "ArrowDown" ? blocks[idx + 1] : blocks[idx - 1];
                if (next) { next.click(); next.scrollIntoView({ behavior: "smooth", block: "nearest" }); }
            }
        }
    });

    setTimeout(function() { vlEmailEditor.updateUndoRedoUI(); }, 100);

    // Auto-open drawer when property panel gets content
    function vlInitDrawerObserver() {
        var panel = document.getElementById("{$propertyPanel}");
        if (!panel) { setTimeout(vlInitDrawerObserver, 500); return; }
        var observer = new MutationObserver(function() {
            if (panel.children.length > 0 && panel.innerHTML.trim() !== "") {
                vlEmailEditor.openDrawer();
            }
        });
        observer.observe(panel, { childList: true, subtree: true });
    }
    vlInitDrawerObserver();

    // Detect visually empty blocks and mark them
    function vlMarkEmptyBlocks() {
        document.querySelectorAll('.vlEmailBlock').forEach(function(block) {
            var content = block.querySelector('.vlEmailBlockContent');
            if (!content) return;
            var isEmpty = content.offsetHeight < 10;
            block.classList.toggle('vlEmailBlockEmpty', isEmpty);
        });
    }
    setTimeout(vlMarkEmptyBlocks, 800);

    // Watch canvas for changes (new blocks, refresh)
    var canvasObs = new MutationObserver(function() {
        setTimeout(vlMarkEmptyBlocks, 300);
    });
    var canvas = document.querySelector('.vlCanvasFrame');
    if (canvas) canvasObs.observe(canvas, { childList: true, subtree: true });

    // Check sessionStorage for pending block (survives page reload)
    var pendingId = sessionStorage.getItem('vlPendingBlockId');
    if (pendingId) vlEmailEditor.waitAndClickBlock(pendingId);
})();
JS;
    }

    protected function editorJs()
    {
        $jsCode = $this->editorJsCode();
        $encoded = base64_encode($jsCode);

        return _Html('<img src="data:," onerror="eval(atob(\'' . $encoded . '\'))" style="display:none">');
    }
}
