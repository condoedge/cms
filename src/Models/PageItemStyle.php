<?php

namespace Anonimatrix\PageEditor\Models;

use App\Models\Cms\Page;

class PageItemStyle extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $casts = [
        'content' => \App\Casts\StylesCast::class,
    ];

    public function pageItem()
    {
        return $this->belongsTo(PageItem::class);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public static function getGenericStylesOfType(string $class, $pageId = null)
    {
        if(!app('page-item-types')->contains($class)) {
            throw new \Exception("Class $class is not a valid page item type. Please check in the PageItemServiceProvider");
        }

        return static::where('block_type', $class::ITEM_NAME)
            ->when($pageId, fn($q) => $q->where('page_id', $pageId))
            ->whereNull('page_item_id')
            ->first()->content;
    }
}