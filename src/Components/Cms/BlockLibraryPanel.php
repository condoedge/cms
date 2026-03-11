<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Kompo\Form;

class BlockLibraryPanel extends Form
{
    public $id = 'block-library-panel';

    protected $prefixGroup = "";
    protected $pageId;

    public const BLOCK_CATEGORIES = [
        'content' => [
            'label' => 'cms::cms.category-content',
            'types' => ['ck', 'h1', 'img', 'button', 'header'],
        ],
        'layout' => [
            'label' => 'cms::cms.category-layout',
            'types' => ['spacer', 'divider', 'boxed-content', 'element-type-1'],
        ],
        'media' => [
            'label' => 'cms::cms.category-media',
            'types' => ['video', 'scribe'],
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

        return _Rows(
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
            $placed = false;
            $itemName = $typeClass::ITEM_NAME;
            $itemGroup = $typeClass::SPECIFIC_GROUP;

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

        return _Rows(
            _Html()->icon(_Sax($icon, 24))->class('vlBlockCardIcon'),
            _Html(__($typeClass::ITEM_TITLE))->class('vlBlockCardLabel'),
        )->class('vlBlockCard')
         ->selfPost('addBlock', ['block_type' => $typeClass::ITEM_NAME])
         ->onSuccess(fn($e) => $e
            ->selfGet('refreshPreview')->inPanel(EmailEditorLayout::PREVIEW_PANEL)
         );
    }

    protected function copyBlockCard()
    {
        return _Rows(
            _Html()->icon(_Sax('copy', 24))->class('vlBlockCardIcon'),
            _Html('cms::cms.copy-block-from-newsletter')->class('vlBlockCardLabel'),
        )->class('vlBlockCard vlBlockCardCopy')
         ->selfGet('getCopyBlockForm')->inPanel(EmailEditorLayout::PROPERTY_PANEL);
    }

    protected function designTab()
    {
        return PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->pageId);
    }

    public function addBlock()
    {
        $blockType = request('block_type');
        $page = PageModel::findOrFail($this->pageId);

        $item = PageItemModel::make();
        $item->page_id = $this->pageId;
        $item->block_type = $blockType;
        $item->order = $page->pageItems()->count();
        $item->save(['skip_validation' => true]);

        return $item;
    }

    public function refreshPreview()
    {
        return PageEditor::getPagePreviewComponent(
            $this->prefixGroup,
            [
                'page_id' => $this->pageId,
                'panel_id' => EmailEditorLayout::PROPERTY_PANEL,
                'with_editor' => true,
            ]
        );
    }

    public function getCopyBlockForm()
    {
        $pages = PageModel::where('id', '!=', $this->pageId);

        $currentPage = PageModel::find($this->pageId);
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
                    ->run('() => { document.getElementById("'.EmailEditorLayout::PROPERTY_PANEL.'").innerHTML = "" }'),
            )->class('justify-between items-center mb-4'),
            _Select('cms::cms.select-newsletter')->name('select_newsletter', false)
                ->options($pageOptions->toArray())
                ->onChange(fn($e) => $e->selfGet('getCopyBlockItems')->inPanel('copy-block-items-panel')),
            _Panel()->id('copy-block-items-panel')->class('mt-3'),
        )->class('p-4');
    }

    public function getCopyBlockItems()
    {
        $pageId = request('select_newsletter');
        if (!$pageId) return _Html('');

        $page = PageModel::findOrFail($pageId);
        $items = $page->orderedMainPageItems()->get();

        $options = $items->mapWithKeys(function ($item) {
            $type = $item->getPageItemType();
            $typeName = $type ? __($type::ITEM_TITLE) : '';
            $zoneName = $item->name_pi ?: $typeName;
            $label = $zoneName . ($item->name_pi ? ' (' . $typeName . ')' : '');
            return [$item->id => $label];
        });

        return _Rows(
            _Select('cms::cms.select-block')->name('select_block', false)
                ->options($options->toArray()),
            _Button('cms::cms.copy-this-block')->icon('duplicate')
                ->selfPost('copyBlockToPage')
                ->onSuccess(fn($e) => $e->selfGet('refreshPreview')->inPanel(EmailEditorLayout::PREVIEW_PANEL))
                ->class('mt-3 w-full'),
        );
    }

    public function copyBlockToPage()
    {
        $sourceItem = PageItemModel::findOrFail(request('select_block'));
        $page = PageModel::findOrFail($this->pageId);

        $newItem = $sourceItem->replicate();
        $newItem->page_id = $this->pageId;
        $newItem->order = $page->pageItems()->count();
        $newItem->page_item_id = null;
        $newItem->group_page_item_id = null;
        $newItem->save(['skip_validation' => true]);

        if ($sourceItem->styles) {
            $newStyles = $sourceItem->styles->replicate();
            $newItem->styles()->save($newStyles);
        }

        $sourceItem->groupPageItems()->each(function ($groupItem) use ($newItem) {
            $newGroupItem = $groupItem->replicate();
            $newGroupItem->group_page_item_id = $newItem->id;
            $newGroupItem->page_id = $newItem->page_id;
            $newGroupItem->save(['skip_validation' => true]);

            if ($groupItem->styles) {
                $newGroupStyles = $groupItem->styles->replicate();
                $newGroupItem->styles()->save($newGroupStyles);
            }
        });
    }
}
