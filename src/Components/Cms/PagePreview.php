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

    public $containerClass = 'flex flex-col external-container';
    public $paginationType = 'Scroll';
	public $itemsWrapperClass = 'px-0 overflow-x-auto overflow-y-auto mini-scroll';
    public $noItemsFound = '';

    protected $panelId;
    protected $withEditor = false;

    public $orderable = 'order';
	public $dragHandle = '.vlBlockDragHandle';

    protected $prefixGroup = "";

    protected $useEmailEditor = true;

    public function created()
    {
        $this->page = $this->prop('page_id') ? PageModel::findOrFail($this->prop('page_id')) : PageModel::make();
        $this->panelId = $this->prop('panel_id') ?: $this->panelId;
        $this->withEditor = $this->prop('with_editor');

        $this->useEmailEditor = $this->isEmailEditorContext();

        $this->perPage = $this->withEditor ? 10 : $this->page->orderedMainPageItems()->count();
        $this->style = $this->withEditor ? 'max-height: 100vh; width: 100%;' : 'width: 100%;';

        $this->itemsWrapperClass .= ' vlQueryWrapperPagePreview';

        if (!$this->withEditor) {
            $this->onLoad(fn($e) => $e->run('() => {$(".external-container").css("background-color", "'. $this->page->getExteriorBackgroundColor() .'")}'));
        }

        $contentMaxWidth = $this->page->getContentMaxWidth();
        if ($this->withEditor && !$this->useEmailEditor) {
            $this->onLoad(fn($e) => $e->run('() => {$(".vlQueryWrapperPagePreview").css({"background-color": "'. $this->page->getContentBackgroundColor() .'", "width": "100%"})}'));
        } else if (!$this->withEditor) {
            $this->onLoad(fn($e) => $e->run('() => {$(".vlQueryWrapperPagePreview").css({"background-color": "'. $this->page->getContentBackgroundColor() .'", "max-width": "'. $contentMaxWidth .'px", "margin": "0 auto"})}'));
        }
    }

    protected function isEmailEditorContext()
    {
        return $this->panelId === EmailEditorLayout::PROPERTY_PANEL;
    }

    public function top()
    {
        if (!$this->withEditor) {
            return _Html('<style>@media (max-width: 600px) { .vlFlexResponsiveColumns { flex-direction: column !important; } }</style>');
        }

        if ($this->useEmailEditor) {
            return $this->emailEditorTop();
        }

        return $this->legacyTop();
    }

    protected function emailEditorTop()
    {
        $hasItems = $this->page->id && $this->page->orderedMainPageItems()->count() > 0;

        return _Rows(
            _Html('<style>@media (max-width: 600px) { .vlFlexResponsiveColumns { flex-direction: column !important; } }</style>'),
            !$hasItems ? null : _Html('')->class('pt-2'),
        );
    }

    protected function legacyTop()
    {
        return _Rows(
            _Html('<style>@media (max-width: 600px) { .vlFlexResponsiveColumns { flex-direction: column !important; } }</style>'),
            _FlexBetween(
                !$this->page->id ? null : _Link('cms::cms.preview-in-browser')->icon('eye')->outlined()->class('p-2 flex items-center gap-1 text-sm')->href('page.preview', ['page_id' => $this->page->id])->inNewTab(),
                _Flex(
                    _Link()->icon('device-mobile')->balloon('cms::cms.preview-mobile', 'down')
                        ->class('p-2 text-gray-500 hover:text-gray-800')
                        ->run('() => { const el = document.querySelector(".vlQueryWrapperPagePreview"); el.style.maxWidth = el.style.maxWidth === "375px" ? "100%" : "375px"; el.style.margin = "0 auto"; }'),
                    _Link()->icon('device-tablet')->balloon('cms::cms.preview-tablet', 'down')
                        ->class('p-2 text-gray-500 hover:text-gray-800')
                        ->run('() => { const el = document.querySelector(".vlQueryWrapperPagePreview"); el.style.maxWidth = el.style.maxWidth === "768px" ? "100%" : "768px"; el.style.margin = "0 auto"; }'),
                    _Link()->icon('desktop-computer')->balloon('cms::cms.preview-desktop', 'down')
                        ->class('p-2 text-gray-500 hover:text-gray-800')
                        ->run('() => { const el = document.querySelector(".vlQueryWrapperPagePreview"); el.style.maxWidth = "100%"; }'),
                )->class('gap-1'),
            )->class('mb-2'),
            _Button('cms::cms.add-zone')->icon('plus')->class('w-full mb-2')->selfGet('getPageItemForm', ['page_id' => $this->page->id])->inPanel($this->panelId),
        );
    }

    public function bottom()
    {
        if (!$this->withEditor || !$this->useEmailEditor) return null;

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

        $newItem = $pageItem->replicate();
        $newItem->order = $pageItem->page->pageItems()->count();
        $newItem->save(['skip_validation' => true]);

        if ($pageItem->styles) {
            $newStyles = $pageItem->styles->replicate();
            $newItem->styles()->save($newStyles);
        }

        $pageItem->groupPageItems()->each(function ($groupItem) use ($newItem) {
            $newGroupItem = $groupItem->replicate();
            $newGroupItem->group_page_item_id = $newItem->id;
            $newGroupItem->save(['skip_validation' => true]);

            if ($groupItem->styles) {
                $newGroupStyles = $groupItem->styles->replicate();
                $newGroupItem->styles()->save($newGroupStyles);
            }
        });
    }

}
