<?php

namespace Anonimatrix\PageEditor\Traits;

trait PageCopyTrait
{
    public function createPageCopy()
    {
        $newPage = $this->replicate();
        $newPage->user_id = auth()->id();
        $newPage->created_at = now();
        $newPage->updated_at = now();
        $newPage->save();

        return $newPage;
    }

    public function createPageCopyWithRelations()
    {
        $newPage = $this->createPageCopy();

        $this->copyItemsPage($newPage->id);

        return $newPage;
    }

    public function copyItemsPage($toPageId)
    {
        $this->pageItems()->each(function($pageItem) use ($toPageId){
			$newPageItem = $pageItem->replicate();
			$newPageItem->page_id = $toPageId;
			$newPageItem->save();
		});
    }
}
