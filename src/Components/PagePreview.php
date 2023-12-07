<?php

namespace Anonimatrix\PageEditor\Components;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Query;

class PagePreview extends Query
{
    public $page;

    public $containerClass = 'flex flex-col items-center';
    public $style = 'max-height: 100vh; max-width: 600px;';
    public $paginationType = 'Scroll';
	public $itemsWrapperClass = 'px-8 overflow-x-auto overflow-y-auto mini-scroll';

    protected $panelId;
    protected $withEditor = false;

    public $orderable = 'order';
	public $dragHandle = '.cursor-move';

    public function created()
    {
        $this->page = PageModel::findOrFail($this->prop('page_id'));
        $this->panelId = $this->prop('panel_id') ?: $this->panelId;
        $this->withEditor = $this->prop('with_editor');

        if(!$this->withEditor) $this->changeBgColor($this->page->getExteriorBackgroundColor());
    }

    public function top()
    {
        return $this->withEditor ? _Rows(
            _Link('campaign.preview-in-browser')->class('w-full bg-blue-100 p-4 flex justify-center mb-2')->href('page.preview', ['page_id' => $this->page->id])->inNewTab(),
            _Button('campaign.add-zone')->class('w-full mb-2')->selfGet('getPageItemForm', ['page_id' => $this->page->id])->inPanel($this->panelId),
        ) : _Html('');
    }

    public function query()
    {
        return $this->page->orderedMainPageItems();
    }

    public function render($pageItem)
    {
        $team = $pageItem->team;
        $pageItemType = $pageItem?->getPageItemType();

        $pageItemType?->setVariables([
            'team_name' => $team->name,
            'team_logo' => $team->emailLogoHtml(),
            'subscribe_to_newsletter' => $team->getLinkHtmlToSubscribe(),
        ]);

        $pageItemType?->setEditPanelId($this->panelId);
        $el = $pageItemType?->toPreviewElement($this->withEditor);

        return $el;
    }

    public function getPageItemForm()
    {
        $itemId = request('item_id');
        $pageId = $this?->page?->id ?? request('page_id');

        return _Rows(
            new PageItemForm($itemId, [
                'page_id' => $pageId,
                'update_order' => !$itemId,
            ])
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
}
