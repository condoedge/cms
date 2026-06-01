<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class BlockLibraryPanel extends Form
{
    public $id = 'block-library-panel';

    protected $prefixGroup = "";
    protected $pageId;

    public function created()
    {
        $this->pageId = $this->prop('page_id');
    }

    public function render()
    {
        return _Rows(
            _SwipeableTabs(
                _Tab($this->blocksTab())->label('cms::cms.blocks')->class('vlBlockLibTabContent'),
                _Tab($this->designTab())->label('cms::cms.design')->class('vlBlockLibTabContent'),
            )->class('vlBlockLibTabs px-6 py-2')->tabParamKey('block_lib_tab'),
        )->class('vlBlockLibPanel');
    }

    protected function blocksTab()
    {
        $categorized = PageEditor::getCategorizedTypes($this->prefixGroup);

        $sections = collect($categorized)
            ->filter(fn ($category) => $category['items']->isNotEmpty())
            ->flatMap(fn ($category) => [
                _Html($category['label'])->class('vlBlockCategoryLabel'),
                _Rows($category['items']->map(fn ($typeClass) => $this->blockCard($typeClass)))
                    ->class('vlBlockGrid'),
            ]);

        return _Rows(
            $this->searchInput(),
            _Rows(
                _Rows($sections->all()),
                $this->copyBlockCard(),
            ),
        );
    }

    protected function searchInput()
    {
        return _Div(
            _Div(
                _Input()->name('search_blocks', false)
                    ->placeholder('cms::cms.search-blocks')
                    ->id('vlBlockSearchInput')
                    ->class('vlBlockSearchInput')
                    ->onInput(fn ($e) => $e->run('() => { if (window.vlPageEditor) vlPageEditor.filterBlocks(document.getElementById("vlBlockSearchInput").value) }')),
            )->class('vlBlockSearchWrap'),
        )->class('vlBlockSearch');
    }

    protected function blockCard($typeClass)
    {
        // Create the block server-side and open its editor in the native drawer
        // (same pattern as the canvas edit link), then refresh the canvas natively.
        return _Link($typeClass::ITEM_TITLE)
            ->icon(_Sax($typeClass::ITEM_ICON, 24))
            ->class('vlBlockCard')
            ->selfGet('addBlockAndEdit', ['block_type' => $typeClass::ITEM_NAME])
            ->inDrawer()
            ->onSuccess(fn ($e) => $e->selfGet('getPagePreview')->inPanel(PageEditorLayout::PREVIEW_PANEL));
    }

    public function addBlockAndEdit()
    {
        $page = PageModel::findOrFail($this->pageId);

        $item = app(PageBlockService::class)->addBlock($page, request('block_type'));

        return PageEditor::getPageItemFormComponent($this->prefixGroup, $item->id, [
            'page_id' => $this->pageId,
        ]);
    }

    public function getPagePreview()
    {
        return PageEditor::getPagePreviewComponent($this->prefixGroup, [
            'page_id' => $this->pageId,
            'panel_id' => PageEditorLayout::PROPERTY_PANEL,
            'with_editor' => true,
        ]);
    }

    protected function copyBlockCard()
    {
        return _Link('cms::cms.copy-block-from-newsletter')
            ->icon(_Sax('copy', 24))
            ->class('vlBlockCard vlBlockCardCopy')
            ->get('page-editor.copy-block-form', [
                'page_id' => $this->pageId,
            ])->inDrawer();
    }

    protected function designTab()
    {
        return PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->pageId);
    }
}
