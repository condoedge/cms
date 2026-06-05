<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Query;

class PagePreview extends Query
{
    public $page;

    public $containerClass = 'flex flex-col external-container';
    public $paginationType = 'Scroll';
	public $itemsWrapperClass = 'px-0 overflow-x-auto overflow-y-auto mini-scroll';
    public $itemsWrapperStyle = '';
    public $noItemsFound = '';

    protected $panelId;
    protected $withEditor = false;

    public $perPage = 1000; // all items, pagination disabled

    // True only for the in-drawer live-preview full-page tab (a fixed-width
    // column). When set: suppress the standalone device toggle (its JS uses
    // GLOBAL selectors and would cross-control the main editor canvas) and skip
    // the exterior background so the narrow column shows just the content frame.
    // Never set on the standalone preview route.
    protected $inDrawer = false;

    // Live-preview pending override: the id of the saved row whose unsaved
    // request() edits should be swapped in (or 'new' to append an unsaved
    // block at the end). Null on every normal/public render. Read-only —
    // PagePreview never persists the hydrated transient model.
    protected $pendingItemId = null;

    public $orderable = 'order';
	public $dragHandle = '.vlBlockDragHandle';

    protected $prefixGroup = "";

    public function created()
    {
        $this->page = $this->prop('page_id') ? PageModel::findOrFail($this->prop('page_id')) : PageModel::make();
        $this->panelId = $this->prop('panel_id') ?: $this->panelId;
        $this->withEditor = $this->prop('with_editor');
        $this->pendingItemId = $this->prop('pending_item_id');
        $this->inDrawer = (bool) $this->prop('in_drawer');

        $this->style = 'width: 100%;';

        $this->itemsWrapperClass .= ' vlQueryWrapperPagePreview';

        if (!$this->withEditor) {
            // On the public path PagePreview owns its backgrounds; in the editor they come
            // from PageEditorLayout's wrapping divs instead. Render them as server-side Kompo
            // styles (no flash, no global jQuery, scoped to this instance): the exterior color
            // on the query root, the content frame (bg + width) on the items wrapper.
            //
            // In the drawer (narrow fixed-width column) skip the exterior background so the
            // column shows just the content frame; keep the content frame everywhere.
            if (!$this->inDrawer) {
                $this->style .= 'background-color:'.$this->page->getExteriorBackgroundColor().';';
            }

            $this->itemsWrapperStyle = 'background-color:'.$this->page->getContentBackgroundColor()
                .';max-width:'.(int) $this->page->getContentMaxWidth().'px;margin:0 auto;';
        }
    }

    public function top()
    {
        if (!$this->withEditor) {
            // In the drawer the toggle would cross-control the main editor canvas
            // (its JS uses global selectors) and a fixed-width column has nothing
            // to toggle anyway — so render no toggle there.
            return $this->inDrawer ? null : $this->standalonePreviewDeviceToggle();
        }

        $hasItems = $this->page->id && $this->page->orderedMainPageItems()->count() > 0;

        return $hasItems ? _Html('')->class('pt-2') : null;
    }

    /**
     * Desktop / mobile toggle for the standalone preview page (opened in a new tab
     * from the editor). Self-contained: each button carries its own handler via run()
     * so the standalone route needs no editor JS (page-editor.js isn't loaded there).
     * Reuses the editor's .vlDeviceToggle* styling from page-editor.scss.
     */
    protected function standalonePreviewDeviceToggle()
    {
        return _Flex(
            _Flex(
                _Link()->icon(_Sax('monitor', 20))
                    ->balloon('cms::cms.preview-desktop', 'down')
                    ->class('vlDeviceToggle vlDeviceToggleActive')
                    ->attr(['data-device' => 'desktop'])
                    ->run($this->deviceToggleJs('desktop')),
                _Link()->icon(_Sax('mobile', 20))
                    ->balloon('cms::cms.preview-mobile', 'down')
                    ->class('vlDeviceToggle')
                    ->attr(['data-device' => 'mobile'])
                    ->run($this->deviceToggleJs('mobile')),
            )->class('vlDeviceToggleGroup'),
        )->class('vlPreviewToggleBar');
    }

    // Per-button toggle handler: sets the active state and resizes the preview wrapper.
    // Inline (not via a shared helper) because the standalone preview route loads no
    // editor JS; the buttons' styling lives in page-editor.scss.
    protected function deviceToggleJs(string $device): string
    {
        $width = $device === 'mobile' ? '393px' : ((int) $this->page->getContentMaxWidth()).'px';

        return "() => {"
            ."document.querySelectorAll('.vlDeviceToggle').forEach(t => t.classList.remove('vlDeviceToggleActive'));"
            ."var a = document.querySelector(\"[data-device='{$device}']\"); if (a) a.classList.add('vlDeviceToggleActive');"
            ."var w = document.querySelector('.vlQueryWrapperPagePreview'); if (w) w.style.maxWidth = '{$width}';"
        ."}";
    }

    public function bottom()
    {
        // Live-preview full-page tab for a NOT-YET-SAVED block: append the
        // hydrated (unsaved) block at the end so the operator sees it in
        // context. Read-only — nothing is persisted.
        if ($this->pendingItemId === 'new') {
            return $this->renderPendingNewBlock();
        }

        if (!$this->withEditor) return null;

        $hasItems = $this->page->id && $this->page->orderedMainPageItems()->count() > 0;

        if (!$hasItems) {
            return _Rows(
                _Html()->icon(_Sax('add-square', 48))->class('text-gray-300 mb-3'),
                _Html('cms::cms.empty-canvas-title')->class('text-base font-semibold text-gray-400 mb-1'),
                _Html('cms::cms.empty-canvas-desc')->class('text-sm text-gray-400 text-center'),
            )->class('vlEmptyCanvas');
        }

        return null;
    }

    public function query()
    {
        return $this->page->orderedMainPageItems();
    }

    public function render($pageItem)
    {
        // Live-preview pending override: when this row is the one being edited
        // in the drawer, swap in an in-memory model carrying the unsaved
        // request() edits so the full-page tab reflects what's being typed.
        // Read-only — the hydrated model is never saved.
        if ($this->pendingItemId && $pageItem?->id && (string) $pageItem->id === (string) $this->pendingItemId) {
            $pageItem = PageItemForm::hydratePendingItem($pageItem, $this->page->id);
        }

        return $this->renderPageItem($pageItem);
    }

    protected function renderPageItem($pageItem)
    {
        $pageItemType = $pageItem?->getPageItemType();

        if (Features::hasFeature('teams')) {
            $team = $pageItem?->page?->team;

            $pageItemType?->setVariables([
                'team_name' => $team?->name,
                'team_logo' => $team?->emailLogoHtml(),
                'subscribe_to_newsletter' => $team?->getLinkHtmlToSubscribe(),
                'contact_name' => $team?->owner?->name,
            ]);
        }

        $pageItemType?->setEditPanelId($this->panelId);

        return $pageItemType?->toPreviewElement($this->withEditor);
    }

    /**
     * Live-preview only: render the unsaved new block (no id yet) hydrated from
     * request() values, appended after the saved page. Read-only.
     */
    protected function renderPendingNewBlock()
    {
        try {
            $pending = PageItemForm::hydratePendingItem(null, $this->page->id);
            $pending->setRelation('page', $this->page);

            $element = $this->renderPageItem($pending);
        } catch (\Throwable $e) {
            $element = null;
        }

        return $element ? _Rows($element)->class('vlLivePreviewPendingNew') : null;
    }

    public function getPageItemForm()
    {
        $itemId = request('item_id');
        $pageId = $this?->page?->id ?? request('page_id');

        return _Rows(
            PageEditor::getPageItemFormComponent($this->prefixGroup, $itemId, [
                'page_id' => $pageId,
                'update_order' => !$itemId,
            ]),
        );
    }

    public function addPageItemColumn($id)
    {
    	$mainPageItem = PageItemModel::findOrFail($id);

        $mainPageItem->addPageItemColumn();
    }

    public function switchColumnOrder($id)
    {
    	$secondPageItem = PageItemModel::findOrFail($id);

        $secondPageItem->switchColumnOrder();
    }

    public function duplicatePageItem()
    {
        $pageItem = PageItemModel::findOrFail(request('item_id'));

        app(PageBlockService::class)->copyToPage($pageItem, $pageItem->page);
    }

}
