<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Models\PageItemStyle;
use App\Cms\ItemTypes\ButtonItem;
use App\Cms\ItemTypes\CKItem;
use Illuminate\Database\Eloquent\Model;
use App\Cms\ItemTypes\H1Item;
use App\Cms\ItemTypes\HeaderItem;
use App\Cms\ItemTypes\ImgItem;
use App\Cms\ItemTypes\KompoItem;
use App\Cms\ItemTypes\VideoItem;
use App\Cms\ItemTypes\ElementType1Item;
use App\Cms\ItemTypes\H2Item;
use Anonimatrix\PageEditor\Cms\PageItemType;

class PageItem extends Model
{
    use \Anonimatrix\PageEditor\Listeners\Observable;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Kompo\Database\HasTranslations;
    use \Anonimatrix\PageEditor\Traits\HasImageTrait;

    public const TYPES = [
        H1Item::class,
        VideoItem::class,
        CKItem::class,
        HeaderItem::class,
        ImgItem::class,
        KompoItem::class,
        ButtonItem::class,
        ElementType1Item::class,
    ];

    public const HIDDEN_TYPES = [
        H2Item::class,
    ];

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
    public function getTeamAttribute()
    {
        return $this->page->user->currentTeam;
    }

    /* CALCULATED FIELDS */
    public static function allPageItemTypes()
    {
        return collect(array_merge(PageItem::TYPES, PageItem::HIDDEN_TYPES));
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
        return $this->background_color ?: $this->page->getContentBackgroundColor();
    }

    public function getTitleColor()
    {
        return $this->title_color ?: $this->page->getTitleColor();
    }

    public function getTextColor()
    {
        return $this->text_color ?: $this->page->getTextColor();
    }

    public function getButtonColor()
    {
        return $this->button_color ?: $this->page->getButtonColor();
    }

    public function getLinkColor()
    {
        return $this->link_color ?: $this->page->getLinkColor();
    }

    public function getFontSize()
    {
        return $this->font_size ?: $this->page->getFontSize();
    }

    public function getFontFamily()
    {
        return "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'";
    }

    public function imageUrl()
    {
        if (!$this->image) {
            return;
        }

        return \Storage::url($this->image['path']);
    }

    public static function columnWidths()
    {
        return [
            1 => ['col-md-6', 'col-md-6'],
            //2 => ['col-md-5', 'col-md-7'],
            3 => ['col-md-4', 'col-md-8'],
            4 => ['col-md-3', 'col-md-9'],
            //5 => ['col-md-7', 'col-md-5'],
            6 => ['col-md-8', 'col-md-4'],
            7 => ['col-md-9', 'col-md-3'],
        ];
    }

    public static function rowClasses()
    {
        return [
            1 => ['grid-2-container', '', ''],
            //2 => ['col-md-5', 'col-md-7'],
            3 => ['grid-3-container', '', 'col-span-2'],
            4 => ['grid-4-container', '', 'col-span-3'],
            //5 => ['col-md-7', 'col-md-5'],
            6 => ['grid-3-container', 'col-span-2', ''],
            7 => ['grid-4-container', 'col-span-3', ''],
        ];
    }

    public static function getColumnWidths($key)
    {
        $cols = PageItem::columnWidths();

        if (array_key_exists($key, $cols) ) {
            return $cols[$key];
        }

        return $cols[1];
    }

    public static function getColumnsKey($col1, $col2)
    {
        return collect(PageItem::columnWidths())
            ->filter(fn($cols) => ($col1 == $cols[0]) && ($col2 == $cols[1]))->keys()->first();
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
        $pageItem->block_type = static::TYPES[0]::ITEM_NAME;
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
