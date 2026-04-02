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
            /* Dynamic styles that depend on PHP variables */
            .vlEditorCenterPanel {
                background: '.$bgColor.';
            }
            .vlCanvasFrame {
                max-width: '.$maxWidth.'px;
                background: '.$contentBg.';
            }
            .vlCanvasFrame.vlMobilePreview {
                background: '.$contentBg.';
            }
            .vlEmailBlock.vlEmailBlockEmpty::after {
                content: attr(data-block-type) " — '.__('cms::cms.click-to-edit').'";
            }
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
