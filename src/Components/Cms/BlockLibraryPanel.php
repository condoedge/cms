<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class BlockLibraryPanel extends Form
{
    public $id = 'block-library-panel';

    protected $prefixGroup = "";
    protected $pageId;

    public const HIDDEN_TYPES = ['komponent', 'boxed_content', 'article', 'newsletter.whats-new-card'];

    public const BLOCK_CATEGORIES = [
        'content' => [
            'label' => 'cms::cms.category-content',
            'types' => ['h1', 'ck', 'button', 'header', 'number_line'],
        ],
        'layout' => [
            'label' => 'cms::cms.category-layout',
            'types' => ['spacer', 'divider', 'newsletter.group'],
        ],
        'media' => [
            'label' => 'cms::cms.category-media',
            'types' => ['video', 'img'],
        ],
        'other' => [
            'label' => 'cms::cms.category-other',
            'types' => [],
        ],
    ];

    public function created()
    {
        $this->pageId = $this->prop('page_id');
    }

    public function render()
    {
        return _Rows(
            _SwipeableTabs(
                _Tab(
                    $this->blocksTab(),
                )->label('cms::cms.blocks')->class('vlBlockLibTabContent'),
                _Tab(
                    $this->designTab(),
                )->label('cms::cms.design')->class('vlBlockLibTabContent'),
            )->class('vlBlockLibTabs px-6 pt-2')->config([
                'tabParamKey' => 'block_lib_tab',
            ]),
        )->class('vlBlockLibPanel');
    }

    protected function blocksTab()
    {
        $availableTypes = PageEditor::getAvailableTypes($this->prefixGroup);
        $categorized = $this->categorizeTypes($availableTypes);

        $elements = collect();

        foreach ($categorized as $categoryKey => $category) {
            if ($category['items']->isEmpty()) continue;

            $elements->push(
                _Html($category['label'])->class('vlBlockCategoryLabel')
            );

            $elements->push(
                _Rows(
                    $category['items']->map(fn($typeClass) => $this->blockCard($typeClass))
                )->class('vlBlockGrid')
            );
        }

        $elements->push($this->copyBlockCard());

        return _Rows(
            $this->searchInput(),
            _Rows(
                $elements,
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
                    ->onInput(fn ($e) => $e->run('() => { if (window.vlEmailEditor) vlEmailEditor.filterBlocks(document.getElementById("vlBlockSearchInput").value) }')),
            )->class('vlBlockSearchWrap'),
        )->class('vlBlockSearch');
    }

    protected function categorizeTypes($types)
    {
        $categorized = collect(static::BLOCK_CATEGORIES)->map(fn($cat) => [
            'label' => $cat['label'],
            'items' => collect(),
        ])->toArray();

        foreach ($types as $typeClass) {
            $itemName = $typeClass::ITEM_NAME;

            if (in_array($itemName, static::HIDDEN_TYPES)) continue;

            $placed = false;

            foreach (static::BLOCK_CATEGORIES as $catKey => $cat) {
                if (in_array($itemName, $cat['types'])) {
                    $categorized[$catKey]['items']->push($typeClass);
                    $placed = true;
                    break;
                }
            }

            if (!$placed) {
                $categorized['other']['items']->push($typeClass);
            }
        }

        return $categorized;
    }

    protected function blockCard($typeClass)
    {
        $icon = defined($typeClass.'::ITEM_ICON') ? $typeClass::ITEM_ICON : 'document-text';
        $url = route('page-editor.add-block', [
            'page_id' => $this->pageId,
            'block_type' => $typeClass::ITEM_NAME,
        ]);

        return _Link($typeClass::ITEM_TITLE)
            ->icon(_Sax($icon, 24))
            ->class('vlBlockCard')
            ->run('() => {
                fetch("'.$url.'", { credentials: "same-origin" })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!window.vlEmailEditor) { window.location.reload(); return; }
                        var blockId = data && data.id ? String(data.id) : null;
                        if (blockId) sessionStorage.setItem("vlPendingBlockId", blockId);
                        vlEmailEditor.refreshPreview();
                        if (blockId) vlEmailEditor.waitAndClickBlock(blockId);
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
            ])->inPanel(EmailEditorLayout::PROPERTY_PANEL)
            ->run('() => { if (window.vlEmailEditor) vlEmailEditor.openDrawer() }');
    }

    protected function designTab()
    {
        return PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->pageId);
    }
}
