<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Models\PageItemStyle;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\Abstracts\PageItemModel;

class PageItem extends PageItemModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Kompo\Database\HasTranslations;
    use \Anonimatrix\PageEditor\Models\Traits\HasImageTrait;

    protected $casts = [
        'image' => 'array',
    ];

    protected $translatable = [
        'title',
        'content',
    ];

	/* RELATIONS */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function pageItem()
    {
        return $this->belongsTo(PageItem::class, 'page_item_id');
    }

    public function pageItems()
    {
        return $this->hasMany(PageItem::class, 'page_item_id');
    }

    public function groupPageItem()
    {
        return $this->belongsTo(PageItem::class, 'group_page_item_id');
    }

    public function groupPageItems()
    {
        return $this->hasMany(PageItem::class, 'group_page_item_id');
    }

    public function styles()
    {
        return $this->hasOne(PageItemStyle::class);
    }

    /* ATTRIBUTES */

    /* CALCULATED FIELDS */
    public static function allPageItemTypes()
    {
        return collect(array_merge(config('page-editor.types'), config('page-editor.hidden_types')));
    }

    public static function blockTypes()
    {
        return collect(PageItem::allPageItemTypes())->mapWithKeys(fn($type) => [$type::ITEM_NAME => $type]);
    }

    public static function blockTypesElements()
    {
        return collect(PageItem::allPageItemTypes())->mapWithKeys(fn($type) => [$type::ITEM_NAME => $type::blockTypeElement()]);
    }

    public function getPageItemType(): ?PageItemType
    {
        $blockTypes = PageItem::blockTypes();

        if(!$blockTypes->has($this->block_type)) {
            return null;
        }

        return new $blockTypes[$this->block_type]($this);
    }

    public function getBackgroundColor()
    {
        return $this->styles?->content?->background_color ?? $this->getPageItemType()->getDefaultBackgroundColor() ?? 'transparent';
    }

    public function getTextColor()
    {
        return $this->styles?->content?->text_color ?? $this->getPageItemType()->getDefaultTextColor() ?? 'black';
    }

    public function getFontSize()
    {
        return $this->styles?->content?->font_size ?? $this->getPageItemType()->getDefaultFontSize() ?? '16px';
    }

    public function getFontFamily()
    {
        return config('page-editor.default_font_family');
    }

    public function imageUrl()
    {
        if (!$this->image) {
            return;
        }

        return \Storage::url($this->image['path']);
    }

    /* ACTIONS */
    public function deletable()
    {
        return $this->page->deletable();
    }

    /* ELEMENTS */
    public function addPageItemColumn()
    {
    	if ($this->page_item_id) {
    		abort(403, 'You cannot add more than one column');
    	}

    	$pageItem = new PageItem();
    	$pageItem->page_id = $this->page_id;
    	$pageItem->page_item_id = $this->id;
        $pageItem->block_type = config('page-editor.types')[0]::ITEM_NAME;
    	$pageItem->save();

        return $pageItem;
    }

    public function switchColumnOrder()
    {
    	$secondPageItem = $this;
    	$firstPageItem = $secondPageItem->pageItem;

    	$secondPageItem->page_item_id = null;
    	$secondPageItem->order = $firstPageItem->order;
    	$secondPageItem->save();

    	$toChangeEls = collect([$firstPageItem, ...$firstPageItem->pageItems]);
        $toChangeEls->each(function ($item) use($secondPageItem)
        {
            $item->page_item_id = $secondPageItem->id;
            $item->order = $item->order + 1;
            $item->save();
        });
    }

    /** SCOPES */
    public function scopeNotLinked($query)
    {
        return $query->whereNull('page_item_id')->whereNull('group_page_item_id');
    }
}
