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
            $this->editorStyles(),
            $this->topBar(),
            $this->editorBody(),
            $this->editorJs(),
        )->class('vlEmailEditorWrapper');
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
        )->class('vlEditorLeftPanel');
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
        )->class('vlEditorCenterPanel');
    }

    protected function rightPanel()
    {
        return _Div(
            _Panel(
                $this->rightPanelEmptyState(),
            )->id(static::PROPERTY_PANEL),
        )->class('vlEditorRightPanel');
    }

    protected function rightPanelEmptyState()
    {
        return _Rows(
            _Html()->icon(_Sax('mouse-circle', 48))->class('text-gray-300 mb-4'),
            _Html('cms::cms.select-block-to-edit')->class('text-sm text-gray-400 text-center'),
        )->class('flex flex-col items-center justify-center h-full py-20');
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
                border: none !important;
                background: transparent !important;
                font-size: 15px;
                font-weight: 600;
                color: #111827;
                padding: 4px 8px !important;
                border-radius: 6px;
                min-width: 200px;
                box-shadow: none !important;
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
                padding: 3px;
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
            .vlEditorPreviewBtn {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 7px 14px;
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
                padding: 7px 20px !important;
                font-size: 13px !important;
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
                width: 260px;
                min-width: 260px;
                background: #ffffff;
                border-right: 1px solid #e5e7eb;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Block Library */
            .vlBlockLibPanel {
                height: 100%;
            }
            .vlBlockLibTabs .vlTabItem {
                font-size: 13px;
                font-weight: 600;
                padding: 12px 0;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .vlBlockLibTabContent {
                padding: 0;
            }
            .vlBlockList {
                padding: 12px;
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
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 14px 8px;
                background: #f9fafb;
                border: 1.5px solid #e5e7eb;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.15s;
                min-height: 80px;
            }
            .vlBlockCard:hover {
                border-color: #93c5fd;
                background: #eff6ff;
                box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
                transform: translateY(-1px);
            }
            .vlBlockCard:active {
                transform: translateY(0);
            }
            .vlBlockCardIcon {
                color: #6b7280;
                margin-bottom: 6px;
            }
            .vlBlockCard:hover .vlBlockCardIcon {
                color: #2563eb;
            }
            .vlBlockCardLabel {
                font-size: 11px;
                font-weight: 600;
                color: #4b5563;
                text-align: center;
                line-height: 1.3;
            }
            .vlBlockCardCopy {
                grid-column: 1 / -1;
                flex-direction: row;
                gap: 8px;
                min-height: auto;
                padding: 10px 14px;
                margin-top: 8px;
            }
            .vlBlockCardCopy .vlBlockCardIcon {
                margin-bottom: 0;
            }
            .vlBlockCardCopy .vlBlockCardLabel {
                font-size: 12px;
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
                border-radius: 32px;
                border: 8px solid #1f2937;
                padding: 20px 0 20px;
                background: '.$contentBg.';
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
                position: relative;
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

            /* Right Panel */
            .vlEditorRightPanel {
                width: 340px;
                min-width: 340px;
                background: #ffffff;
                border-left: 1px solid #e5e7eb;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Block selection & hover in canvas */
            .vlEmailBlock {
                border: 2px solid transparent;
                border-radius: 4px;
                transition: border-color 0.15s, box-shadow 0.15s;
                cursor: pointer;
                position: relative;
            }
            .vlEmailBlock:hover {
                border-color: #93c5fd;
            }
            .vlEmailBlock.vlEmailBlockSelected {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 1px #2563eb;
            }

            /* Block action toolbar */
            .vlBlockActions {
                display: none;
                position: absolute;
                top: -36px;
                right: 8px;
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

            /* Override kompo defaults inside editor */
            .vlEditorRightPanel .vlCard { box-shadow: none !important; border: none !important; }
            .vlEditorLeftPanel .vlTabPaneContent { padding: 0 !important; }
        </style>');
    }

    protected function editorJs()
    {
        return _Html('<script>
            window.vlEmailEditor = {
                setDevice: function(device) {
                    var frame = document.querySelector(".vlCanvasFrame");
                    var toggles = document.querySelectorAll(".vlDeviceToggle");

                    toggles.forEach(function(t) { t.classList.remove("vlDeviceToggleActive"); });

                    var activeToggle = document.querySelector("[data-device=\"" + device + "\"]");
                    if (activeToggle) activeToggle.classList.add("vlDeviceToggleActive");

                    if (!frame) return;

                    if (device === "mobile") {
                        frame.classList.add("vlMobilePreview");
                    } else {
                        frame.classList.remove("vlMobilePreview");
                    }
                },

                selectBlock: function(blockEl) {
                    document.querySelectorAll(".vlEmailBlock").forEach(function(b) {
                        b.classList.remove("vlEmailBlockSelected");
                    });
                    if (blockEl) {
                        blockEl.classList.add("vlEmailBlockSelected");
                    }
                },

                showToast: function(message) {
                    var existing = document.querySelector(".vlEditorToast");
                    if (existing) existing.remove();

                    var toast = document.createElement("div");
                    toast.className = "vlEditorToast";
                    toast.textContent = message;
                    document.body.appendChild(toast);

                    setTimeout(function() {
                        if (toast.parentNode) toast.remove();
                    }, 2500);
                }
            };
        </script>');
    }
}
