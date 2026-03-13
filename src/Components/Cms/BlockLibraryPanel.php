<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
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
            _Tabs(
                _Tab(
                    $this->blocksTab(),
                )->label('cms::cms.blocks')->class('vlBlockLibTabContent'),
                _Tab(
                    $this->designTab(),
                )->label('cms::cms.design')->class('vlBlockLibTabContent'),
            )->class('vlBlockLibTabs'),
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
                _Html(__($category['label']))->class('vlBlockCategoryLabel')
            );

            $elements->push(
                _Rows(
                    ...$category['items']->map(fn($typeClass) => $this->blockCard($typeClass))
                )->class('vlBlockGrid')
            );
        }

        $elements->push($this->copyBlockCard());

        // Search bar as first element (raw HTML, no Kompo interaction interference)
        $search = _Html('<div class="vlBlockSearch"><div class="vlBlockSearchWrap"><input type="text" class="vlBlockSearchInput" placeholder="'.__('cms::cms.search-blocks').'" oninput="if(window.vlEmailEditor)vlEmailEditor.filterBlocks(this.value)" /></div></div>');

        return _Rows(
            $search,
            ...$elements,
        )->class('vlBlockList');
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

        return _Link(__($typeClass::ITEM_TITLE))
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

    public function getCopyBlockForm($pageId = null)
    {
        $pageId = $pageId ?: $this->pageId;

        $pages = PageModel::where('id', '!=', $pageId);

        $currentPage = PageModel::find($pageId);
        if ($currentPage?->team_id) {
            $pages->where('team_id', $currentPage->team_id);
        }

        $pageOptions = $pages->orderByDesc('updated_at')
            ->get()
            ->mapWithKeys(fn($page) => [$page->id => $page->title]);

        return _Rows(
            _Flex(
                _Html('cms::cms.copy-block-from-newsletter')->class('font-semibold text-sm'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { if (window.vlEmailEditor) vlEmailEditor.closeDrawer() }'),
            )->class('justify-between items-center mb-4'),
            _Select('cms::cms.select-newsletter')->name('select_newsletter', false)
                ->options($pageOptions->toArray())
                ->onChange(fn($e) => $e->get('page-editor.copy-block-items', [
                    'page_id' => $pageId,
                ])->inPanel('copy-block-items-panel')),
            _Panel()->id('copy-block-items-panel')->class('mt-3'),
        )->class('p-4');
    }

}
