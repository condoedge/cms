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

    public $orderable = 'order';
	public $dragHandle = '.vlBlockDragHandle';

    protected $prefixGroup = "";

    public function created()
    {
        $this->page = $this->prop('page_id') ? PageModel::findOrFail($this->prop('page_id')) : PageModel::make();
        $this->panelId = $this->prop('panel_id') ?: $this->panelId;
        $this->withEditor = $this->prop('with_editor');

        $this->perPage = $this->withEditor ? 10 : $this->page->orderedMainPageItems()->count();
        $this->style = 'width: 100%;';

        $this->itemsWrapperClass .= ' vlQueryWrapperPagePreview';

        if (!$this->withEditor) {
            // On the public path PagePreview owns its backgrounds; in the editor they come
            // from PageEditorLayout's wrapping divs instead. Render them as server-side Kompo
            // styles (no flash, no global jQuery, scoped to this instance): the exterior color
            // on the query root, the content frame (bg + width) on the items wrapper.
            $this->style .= 'background-color:'.$this->page->getExteriorBackgroundColor().';';

            $this->itemsWrapperStyle = 'background-color:'.$this->page->getContentBackgroundColor()
                .';max-width:'.(int) $this->page->getContentMaxWidth().'px;margin:0 auto;';
        }
    }

    public function top()
    {
        if (!$this->withEditor) {
            return null;
        }

        $hasItems = $this->page->id && $this->page->orderedMainPageItems()->count() > 0;

        return $hasItems ? _Html('')->class('pt-2') : null;
    }

    public function bottom()
    {
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
        $pageItemType = $pageItem?->getPageItemType();

        if (Features::hasFeature('teams')) {
            $team = $pageItem->page->team;

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
