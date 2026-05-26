<?php

namespace Anonimatrix\PageEditor\Services;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;

class PageBlockService
{
    public function addBlock($page, string $blockType)
    {
        $item = PageItemModel::make();
        $item->page_id = $page->id;
        $item->block_type = $blockType;
        $item->order = $page->pageItems()->count();
        $item->save(['skip_validation' => true]);

        return $item;
    }

    public function copyableSourcePages(int $currentPageId)
    {
        $query = PageModel::where('id', '!=', $currentPageId);

        $currentPage = PageModel::find($currentPageId);
        if ($currentPage?->team_id) {
            $query->where('team_id', $currentPage->team_id);
        }

        return $query->orderByDesc('updated_at')->get();
    }

    public function copyableItemOptions(int $sourcePageId): array
    {
        $page = PageModel::find($sourcePageId);
        if (!$page) return [];

        return $page->orderedMainPageItems()->get()->mapWithKeys(function ($item) {
            $type = $item->getPageItemType();
            $typeName = $type ? __($type::ITEM_TITLE) : '';
            $zoneName = $item->name_pi ?: $typeName;
            $label = $zoneName . ($item->name_pi ? ' (' . $typeName . ')' : '');
            return [$item->id => $label];
        })->toArray();
    }

    public function copyAllItemsToPage($sourcePage, $targetPage): void
    {
        $sourcePage->orderedMainPageItems()->get()->each(
            fn ($item) => $this->copyToPage($item, $targetPage),
        );
    }

    public function copyToPage($sourceItem, $targetPage)
    {
        $newItem = $sourceItem->replicate();
        $newItem->page_id = $targetPage->id;
        $newItem->order = $targetPage->pageItems()->count();
        $newItem->page_item_id = null;
        $newItem->group_page_item_id = null;
        $newItem->save(['skip_validation' => true]);

        $this->replicateStyles($sourceItem, $newItem);

        $sourceItem->groupPageItems()->each(function ($groupItem) use ($newItem) {
            $newGroupItem = $groupItem->replicate();
            $newGroupItem->group_page_item_id = $newItem->id;
            $newGroupItem->page_id = $newItem->page_id;
            $newGroupItem->save(['skip_validation' => true]);

            $this->replicateStyles($groupItem, $newGroupItem);
        });

        return $newItem;
    }

    protected function replicateStyles($source, $target): void
    {
        if (!$source->styles) return;

        $newStyles = $source->styles->replicate();
        $target->styles()->save($newStyles);
    }
}
