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
        $this->style = $this->withEditor ? 'width: 100%;' : 'width: 100%;';

        $this->itemsWrapperClass .= ' vlQueryWrapperPagePreview';

        if (!$this->withEditor) {
            // json_encode keeps colors/sizes from breaking out of the JS string literal even if
            // they ever contained quotes or backslashes.
            $exterior = json_encode($this->page->getExteriorBackgroundColor());
            $content = json_encode($this->page->getContentBackgroundColor());
            $maxWidth = json_encode($this->page->getContentMaxWidth().'px');

            // Initial CSS setup + bind the device toggle here. _Html()-injected
            // <script> tags don't execute (innerHTML strips them), so the toggle
            // listener has to be wired up via this onLoad hook instead.
            $deviceJs = <<<JS
() => {
    \$('.external-container').css('background-color', {$exterior});
    \$('.vlQueryWrapperPagePreview').css({'background-color': {$content}, 'max-width': {$maxWidth}, 'margin': '0 auto'});

    if (window.__vlStandalonePreviewBound) return;
    window.__vlStandalonePreviewBound = true;
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.vlStandaloneDeviceBtn');
        if (!btn) return;
        var d = btn.getAttribute('data-d');
        var c = document.querySelector('.external-container');
        if (c) c.classList.toggle('vlStandalonePreviewMobile', d === 'mobile');
        document.querySelectorAll('.vlStandaloneDeviceBtn').forEach(function (b) {
            b.classList.toggle('vlStandaloneDeviceBtnActive', b.getAttribute('data-d') === d);
        });
    });
}
JS;

            $this->onLoad(fn ($e) => $e->run($deviceJs));
        }
    }

    public function top()
    {
        if (!$this->withEditor) {
            return _Rows(
                $this->responsiveColumnsStyle(),
                $this->standalonePreviewDeviceToggle(),
            );
        }

        $hasItems = $this->page->id && $this->page->orderedMainPageItems()->count() > 0;

        return _Rows(
            $this->responsiveColumnsStyle(),
            !$hasItems ? null : _Html('')->class('pt-2'),
        );
    }

    // Static CSS (no interpolation). Inline so the public preview keeps working when
    // the host hasn't imported resources/scss/page-editor.scss.
    protected function responsiveColumnsStyle()
    {
        return _Html('<style>@media (max-width:600px){.vlFlexResponsiveColumns{flex-direction:column !important;}}</style>');
    }

    /**
     * Desktop / mobile toggle for the standalone preview page (opened in a new
     * tab from the editor). Mobile applies an iPhone 17 frame (402 × 874) on the
     * preview wrapper, identical proportions to the in-editor mobile preview.
     * Implemented as inline HTML + JS so it works without depending on the
     * host's compiled editor stylesheet/JS bundle.
     */
    protected function standalonePreviewDeviceToggle()
    {
        $desktopLabel = __('cms::cms.preview-desktop');
        $mobileLabel = __('cms::cms.preview-mobile');

        $style = <<<'CSS'
<style>
.vlStandalonePreviewBar {
    display: flex; justify-content: center; gap: 4px;
    padding: 12px; background: #f3f4f6; border-radius: 8px;
    margin: 0 auto 16px; max-width: 240px;
}
.vlStandaloneDeviceBtn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; border-radius: 6px;
    font-size: 13px; font-weight: 500; color: #6b7280;
    background: transparent; border: 0; cursor: pointer;
    transition: all .15s;
}
.vlStandaloneDeviceBtn:hover { color: #111827; }
.vlStandaloneDeviceBtnActive {
    background: #ffffff; color: #111827;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}
.vlStandalonePreviewMobile .vlQueryWrapperPagePreview {
    max-width: 402px !important;
    min-height: 874px;
    border: 8px solid #1f2937;
    border-radius: 32px;
    padding: 20px 0;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.15);
    margin: 0 auto !important;
    overflow-y: auto;
}
.vlStandalonePreviewMobile .vlQueryWrapperPagePreview::before {
    content: "";
    display: block;
    width: 40px; height: 4px;
    background: #374151; border-radius: 2px;
    margin: 0 auto 12px;
}
</style>
CSS;

        $svgDesktop = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
        $svgMobile = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>';

        // Click listener for these buttons is wired up via onLoad() in
        // created() — inline <script> tags here would not execute.
        return _Html(
            $style .
            '<div class="vlStandalonePreviewBar">' .
                '<button type="button" class="vlStandaloneDeviceBtn vlStandaloneDeviceBtnActive" data-d="desktop">' .
                    $svgDesktop . '<span>' . e($desktopLabel) . '</span>' .
                '</button>' .
                '<button type="button" class="vlStandaloneDeviceBtn" data-d="mobile">' .
                    $svgMobile . '<span>' . e($mobileLabel) . '</span>' .
                '</button>' .
            '</div>'
        );
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
