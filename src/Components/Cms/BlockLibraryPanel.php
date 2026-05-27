<?php

namespace Anonimatrix\PageEditor\Components\Cms;

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
        $url = route('page-editor.add-block', [
            'page_id' => $this->pageId,
            'block_type' => $typeClass::ITEM_NAME,
        ]);

        return _Link($typeClass::ITEM_TITLE)
            ->icon(_Sax($typeClass::ITEM_ICON, 24))
            ->class('vlBlockCard')
            ->run('() => {
                fetch("'.$url.'", { credentials: "same-origin" })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!window.vlPageEditor) { window.location.reload(); return; }
                        var blockId = data && data.id ? String(data.id) : null;
                        if (blockId) sessionStorage.setItem("vlPendingBlockId", blockId);
                        vlPageEditor.refreshPreview();
                        if (blockId) vlPageEditor.waitAndClickBlock(blockId);
                    });
            }');
    }

    protected function copyBlockCard()
    {
        return _Link('cms::cms.copy-block-from-newsletter')
            ->icon(_Sax('copy', 24))
            ->class('vlBlockCard vlBlockCardCopy')
            ->get('page-editor.copy-block-form', [
                'page_id' => $this->pageId,
            ])->inPanel(PageEditorLayout::PROPERTY_PANEL)
            ->run('() => { if (window.vlPageEditor) vlPageEditor.openDrawer() }');
    }

    protected function designTab()
    {
        return PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->pageId);
    }
}
