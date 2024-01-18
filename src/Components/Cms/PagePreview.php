<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Query;

class PagePreview extends Query
{
    public $page;

    public $containerClass = 'flex flex-col items-center';
    public $paginationType = 'Scroll';
	public $itemsWrapperClass = 'px-8 overflow-x-auto overflow-y-auto mini-scroll';

    protected $panelId;
    protected $withEditor = false;

    public $orderable = 'order';
	public $dragHandle = '.cursor-move';

    protected $prefixGroup = "";

    public function created()
    {
        $this->page = $this->prop('page_id') ? PageModel::findOrFail($this->prop('page_id')) : PageModel::make();
        $this->panelId = $this->prop('panel_id') ?: $this->panelId;
        $this->withEditor = $this->prop('with_editor');

        $this->perPage = $this->withEditor ? 10 : $this->page->orderedMainPageItems()->count();
        $this->style = $this->withEditor ? 'max-height: 100vh; width: 100%;' : 'width: 100%;';
        // if(!$this->withEditor) $this->onLoad(fn($e) => $e->run('() => {$("body").css("background-color", "'. $this->page->getExteriorBackgroundColor() .'")}'));
    }

    public function top()
    {
        return $this->withEditor ? _Rows(
            !$this->page->id ? null : _Link('cms::cms.preview-in-browser')->class('w-full bg-blue-100 p-4 flex justify-center mb-2')->href('page.preview', ['page_id' => $this->page->id])->inNewTab(),
            _Button('cms::cms.add-zone')->class('w-full mb-2')->selfGet('getPageItemForm', ['page_id' => $this->page->id])->inPanel($this->panelId),
        ) : _Html('');
    }

    public function query()
    {
        return $this->page->orderedMainPageItems();
    }

    public function render($pageItem)
    {
        $pageItemType = $pageItem?->getPageItemType();

        if (Features::hasFeature('teams')) {
            $team = $pageItem->team;
            $team = $pageItem->page->team;

            $pageItemType?->setVariables([
                'team_name' => $team?->name,
                'team_logo' => $team?->emailLogoHtml(),
                'subscribe_to_newsletter' => $team?->getLinkHtmlToSubscribe(),
                'contact_name' => $team?->owner?->name,
            ]);
        }

        $pageItemType?->setEditPanelId($this->panelId);
        $el = $pageItemType?->toPreviewElement($this->withEditor);

        return $el;
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
}
