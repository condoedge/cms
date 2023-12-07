<?php

namespace Anonimatrix\PageEditor\Providers;

use Anonimatrix\PageEditor\Models\PageItem;

class PageItemServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * The types of items that can be added to a page.
     *  @var PageItemType[] $itemsTypes
     */
    // public array $itemsTypes = [
    //     \App\Cms\ItemTypes\H1Item::class,
    //     \App\Cms\ItemTypes\H2Item::class,
    //     \App\Cms\ItemTypes\ImgItem::class,
    //     \App\Cms\ItemTypes\VideoItem::class,
    //     \App\Cms\ItemTypes\CKItem::class,
    //     \App\Cms\ItemTypes\ButtonItem::class,
    //     \App\Cms\ItemTypes\KompoItem::class,
    //     \App\Cms\ItemTypes\ElementType1Item::class,
    // ];

    public function register(): void
    {
        $this->app->bind('page-item-types', function () {
            return collect(config('page-editor.types'));
        });
    }

    public function boot(): void
    {
        PageItem::creating(function ($pageItem) {
            $pageItem->getPageItemType()?->beforeSave($pageItem);
        });
        
        PageItem::created(function ($pageItem) {
            $pageItem->getPageItemType()?->afterSave($pageItem);
        });
    }

}