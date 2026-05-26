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
        $propertyPanel = static::PROPERTY_PANEL;

        return <<<JS
(function() {
    if (window.vlEmailEditor) return;

    window.vlEmailEditor = {
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

        refreshPreview: function() {
            var wrapper = document.querySelector(".vlQueryWrapperPagePreview");
            if (!wrapper) { window.location.reload(); return; }
            var el = wrapper;
            var vm = null;
            for (var i = 0; i < 8 && el; i++) {
                if (el.__vue__ && typeof el.__vue__.browseQuery === "function") {
                    vm = el.__vue__;
                    break;
                }
                el = el.parentElement;
            }
            if (vm) { vm.browseQuery(); }
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
            if (panel === "blocks") {
                var leftPanel = document.querySelector(".vlEditorLeftPanel");
                if (leftPanel) leftPanel.classList.toggle("vlPanelMobileOpen");
            }
            else if (panel === "properties") { this.openDrawer(); }
        }
    };

    // Portal the drawer + backdrop into <body> so position:fixed escapes any
    // ancestor with a transform (Kompo tabs, animations, etc.) that would
    // otherwise turn it into the new containing block.
    function vlPortalDrawer() {
        var drawer = document.querySelector(".vlEditorRightPanel");
        var backdrop = document.querySelector(".vlDrawerBackdrop");
        if (!drawer || !backdrop) { setTimeout(vlPortalDrawer, 200); return; }
        if (drawer.parentElement !== document.body) document.body.appendChild(drawer);
        if (backdrop.parentElement !== document.body) document.body.appendChild(backdrop);
    }
    vlPortalDrawer();

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
        if (isInput) return;
        if (e.key === "Escape") {
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
