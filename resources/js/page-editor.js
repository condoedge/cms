(function () {
    if (window.vlPageEditor) return;

    window.vlPageEditor = {
        setDevice: function (device) {
            var frame = document.querySelector('.vlCanvasFrame');
            var toggles = document.querySelectorAll('.vlDeviceToggle');
            toggles.forEach(function (t) { t.classList.remove('vlDeviceToggleActive'); });
            var activeToggle = document.querySelector("[data-device='" + device + "']");
            if (activeToggle) activeToggle.classList.add('vlDeviceToggleActive');
            if (!frame) return;
            if (device === 'mobile') { frame.classList.add('vlMobilePreview'); }
            else { frame.classList.remove('vlMobilePreview'); }
        },

        // Highlight-only: the native Kompo drawer opens via the block's own onClick.
        // This just marks the clicked block as selected on the canvas.
        selectBlock: function (blockEl) {
            document.querySelectorAll('.vlPageBlock').forEach(function (b) {
                b.classList.remove('vlPageBlockSelected');
            });
            if (blockEl) {
                blockEl.classList.add('vlPageBlockSelected');
            }
        },

        getSelectedBlock: function () {
            return document.querySelector('.vlPageBlockSelected');
        },

        clearSelection: function () {
            document.querySelectorAll('.vlPageBlock').forEach(function (b) {
                b.classList.remove('vlPageBlockSelected');
            });
        },

        filterBlocks: function (query) {
            var cards = document.querySelectorAll('.vlBlockCard:not(.vlBlockCardCopy)');
            var categories = document.querySelectorAll('.vlBlockCategoryLabel');
            var grids = document.querySelectorAll('.vlBlockGrid');
            var q = (query || '').toLowerCase().trim();
            cards.forEach(function (card) {
                var label = card.querySelector('.vlBlockCardLabel, .vlLabel');
                var text = label ? label.textContent.toLowerCase() : card.textContent.toLowerCase();
                card.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
            });
            grids.forEach(function (grid, i) {
                var visibleCards = grid.querySelectorAll('.vlBlockCard:not([style*="display: none"])');
                var catLabel = categories[i];
                if (catLabel) catLabel.style.display = visibleCards.length > 0 ? '' : 'none';
                grid.style.display = visibleCards.length > 0 ? '' : 'none';
            });
        },

        toggleMobilePanel: function (panel) {
            if (panel === 'blocks') {
                var leftPanel = document.querySelector('.vlEditorLeftPanel');
                if (leftPanel) leftPanel.classList.toggle('vlPanelMobileOpen');
            }
        },

        // Close the topmost native Kompo drawer by triggering its built-in X
        // (the drawer owns open/close; this just reaches its close affordance for
        // keyboard shortcuts and block-to-block navigation).
        closeTopDrawer: function () {
            var closes = document.querySelectorAll('.vlDrawerClose');
            if (closes.length) closes[closes.length - 1].click();
        }
    };

    document.addEventListener('click', function (e) {
        // Closing the drawer (native X, Esc, or arrow-nav all route through it) clears the canvas highlight.
        if (e.target.closest('.vlDrawerClose')) { vlPageEditor.clearSelection(); return; }
        if (e.target.closest('.vlBlockActions')) return;
        var block = e.target.closest('.vlPageBlock');
        if (block) { vlPageEditor.selectBlock(block); }
    }, true);

    document.addEventListener('keydown', function (e) {
        var tag = e.target.tagName.toLowerCase();
        var isInput = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;

        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var saveBtn = document.querySelector('.vlEditorSaveBtn');
            if (saveBtn) saveBtn.click();
            return;
        }
        if (isInput) return;
        if (e.key === 'Escape') {
            vlPageEditor.closeTopDrawer();
            return;
        }
        if (e.key === 'Delete' || e.key === 'Backspace') {
            var selected = vlPageEditor.getSelectedBlock();
            if (selected) {
                var deleteBtn = selected.querySelector('.vlBlockActionBtnDanger');
                if (deleteBtn) { e.preventDefault(); deleteBtn.click(); }
            }
        }
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            var selected = vlPageEditor.getSelectedBlock();
            if (selected) {
                e.preventDefault();
                var blocks = Array.from(document.querySelectorAll('.vlPageBlock'));
                var idx = blocks.indexOf(selected);
                var next = e.key === 'ArrowDown' ? blocks[idx + 1] : blocks[idx - 1];
                if (next) {
                    // Close the current block's drawer before opening the next one, so drawers don't stack.
                    vlPageEditor.closeTopDrawer();
                    next.click();
                    next.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }
    });

    // ---- Live preview bridge (Phase 4) ----
    // A single stateless cosmetic listener scoped to the property drawer: when
    // the operator edits a field, re-click the ACTIVE preview tab button so the
    // server re-renders the preview panel from the unsaved form values. Each tab
    // button selfGets its own render method (+ withAllFormValues), so re-clicking
    // the active one refreshes whichever tab is showing — no shared hidden state.
    //  - input (debounced): only when the active tab is "block" (live as you
    //    type); skipped on the "page" tab (too heavy per keystroke).
    //  - change (commit/blur): always, so the full-page tab refreshes on commit.
    var vlPreviewDebounce = null;

    function vlActivePreviewTabBtn(drawer) {
        return drawer.querySelector('.vlLivePreviewTabActive');
    }

    function vlRefreshActivePreview(drawer) {
        var active = vlActivePreviewTabBtn(drawer);
        if (active) active.click();
    }

    function vlIsPreviewControl(target) {
        // Ignore the preview tab buttons / panel themselves so editing inside the
        // preview (or clicking a tab) doesn't recursively re-trigger a refresh.
        return !!(target.closest && (
            target.closest('.vlLivePreviewTabs') ||
            target.closest('.vlLivePreviewCanvas')
        ));
    }

    document.addEventListener('input', function (e) {
        var drawer = e.target.closest && e.target.closest('.vlPropertyDrawer');
        if (!drawer || vlIsPreviewControl(e.target)) return;
        var active = vlActivePreviewTabBtn(drawer);
        if (!active || active.getAttribute('data-preview-tab') !== 'block') return;
        if (vlPreviewDebounce) clearTimeout(vlPreviewDebounce);
        vlPreviewDebounce = setTimeout(function () { vlRefreshActivePreview(drawer); }, 300);
    }, true);

    document.addEventListener('change', function (e) {
        var drawer = e.target.closest && e.target.closest('.vlPropertyDrawer');
        if (!drawer || vlIsPreviewControl(e.target)) return;
        if (vlPreviewDebounce) { clearTimeout(vlPreviewDebounce); vlPreviewDebounce = null; }
        vlRefreshActivePreview(drawer);
    }, true);

    // Stays as JS deliberately: the server already renders a .vlEmptyBlockPlaceholder
    // for blocks whose toElement() is null (PageItemType::toPreviewSinglePageItem).
    // This catches the OTHER case — a block that renders real markup but collapses to
    // ~0px (e.g. content of " " or "<p></p>"). That requires post-render layout
    // measurement (offsetHeight), which has no server/kompo equivalent.
    function vlMarkEmptyBlocks() {
        document.querySelectorAll('.vlPageBlock').forEach(function (block) {
            var content = block.querySelector('.vlPageBlockContent');
            if (!content) return;
            var isEmpty = content.offsetHeight < 10;
            block.classList.toggle('vlPageBlockEmpty', isEmpty);
        });
    }
    setTimeout(vlMarkEmptyBlocks, 800);

    var canvasObs = new MutationObserver(function () {
        setTimeout(vlMarkEmptyBlocks, 300);
    });
    var canvas = document.querySelector('.vlCanvasFrame');
    if (canvas) canvasObs.observe(canvas, { childList: true, subtree: true });
})
