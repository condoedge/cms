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
        $this->prefixGroup = $this->prop('prefix_group') ?? "";
    }

    public function render()
    {
        return _Rows(
            _SwipeableTabs(
                _Tab($this->blocksTab())->label('cms::cms.blocks')->class('vlBlockLibTabContent'),
                _Tab($this->designTab())->label('cms::cms.design')->class('vlBlockLibTabContent'),
                _Tab($this->extraInfoTab())->label('cms::cms.extra-info')->class('vlBlockLibTabContent'),
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
        return _Link($typeClass::ITEM_TITLE)
            ->icon(_Sax($typeClass::ITEM_ICON, 24))
            ->class('vlBlockCard')
            ->openInEditorDrawer('addBlock', [
                'page_id' => $this->pageId,
                'block_type' => $typeClass::ITEM_NAME,
            ]);
    }

    public function addBlock()
    {
        $pageId = request('page_id');
        $blockType = request('block_type');

        $page = PageModel::findOrFail($pageId);
        $blockService = app(PageBlockService::class);
        $item = $blockService->addBlock($page, $blockType);

        return _Rows(
            PageEditor::getPageItemFormComponent(null, $item->id, [
                'page_id' => $pageId,
                'update_order' => !$item->id,
            ]),
        );
    }

    protected function copyBlockCard()
    {
        return _Link('cms::cms.copy-block-from-newsletter')
            ->icon(_Sax('copy', 24))
            ->class('vlBlockCard vlBlockCardCopy')
            ->openInEditorDrawer('copyBlock', [
                'page_id' => $this->pageId,
            ]);
    }

    public function copyBlock()
    {
        return _Rows(
            PageEditor::getPageItemFormComponent(null, null, [
                'page_id' => $this->pageId,
                'update_order' => true,
                'preset_copy' => true,
            ]),
        );
    }

    protected function designTab()
    {
        return PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->pageId);
    }

    protected function extraInfoTab()
    {
        return PageEditor::getPageInfoFormComponent($this->prefixGroup, $this->pageId, [
            'page_id' => $this->pageId,
            'only_partial_info' => true,
        ]);
    }
}
