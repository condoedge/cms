<?php

namespace Anonimatrix\PageEditor\Providers;

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

}