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

        // Highlight only. The block's editor opens through its native Kompo
        // ->inDrawer() onClick action, not from JS.
        selectBlock: function (blockEl) {
            document.querySelectorAll('.vlPageBlock').forEach(function (b) {
                b.classList.remove('vlPageBlockSelected');
            });
            if (blockEl) blockEl.classList.add('vlPageBlockSelected');
        },

        getSelectedBlock: function () {
            return document.querySelector('.vlPageBlockSelected');
        },

        clearSelection: function () {
            document.querySelectorAll('.vlPageBlock').forEach(function (b) {
                b.classList.remove('vlPageBlockSelected');
            });
        },

        showToast: function (message) {
            var existing = document.querySelector('.vlEditorToast');
            if (existing) existing.remove();
            var toast = document.createElement('div');
            toast.className = 'vlEditorToast';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 2500);
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

        refreshPreview: function () {
            var wrapper = document.querySelector('.vlQueryWrapperPagePreview');
            if (!wrapper) { window.location.reload(); return; }
            var el = wrapper;
            var vm = null;
            for (var i = 0; i < 8 && el; i++) {
                if (el.__vue__ && typeof el.__vue__.browseQuery === 'function') {
                    vm = el.__vue__;
                    break;
                }
                el = el.parentElement;
            }
            if (vm) { vm.browseQuery(); }
            else { window.location.reload(); }
        },

        toggleMobilePanel: function (panel) {
            if (panel === 'blocks') {
                var leftPanel = document.querySelector('.vlEditorLeftPanel');
                if (leftPanel) leftPanel.classList.toggle('vlPanelMobileOpen');
            }
        }
    };

    // Highlight the clicked block. The drawer itself is opened by the block's
    // native Kompo onClick (->inDrawer()).
    document.addEventListener('click', function (e) {
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
                if (next) { next.click(); next.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            }
        }
    });

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
