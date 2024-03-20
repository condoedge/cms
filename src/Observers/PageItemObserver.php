<?php

namespace Anonimatrix\PageEditor\Observers;

use Anonimatrix\PageEditor\Models\PageItem;

class PageItemObserver
{
    /**
     * Handle the PageItem "deleted" event.
     */
    public function deleted(PageItem $pageItem): void
    {
        $pageItems = $pageItem->pageItems;

        if ($pageItems->count() > 0) {
            $firstPageItem = $pageItems->first();

            $firstPageItem->page_item_id = null;
            $firstPageItem->order = $pageItem->order;
            $firstPageItem->save();

            $pageItems->skip(1)->each(function ($item) use ($firstPageItem) {
                $item->page_item_id = $firstPageItem->id;
                $item->order = null;
                $item->save();
            });
        }
    }
}
