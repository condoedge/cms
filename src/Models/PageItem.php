<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Models\PageItemStyle;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\Abstracts\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;

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
        return $this->hasOne(PageItemStyle::class, 'page_item_id', 'id');
    }

    /* ATTRIBUTES */

    /* CALCULATED FIELDS */
    public function getOrCreateStyles()
    {
        if (!$this->styles) {
            $styleModel = PageItemStyleModel::make();
            $styleModel->content = "";
            $this->styles()->save($styleModel);

            return $styleModel->refresh();
        } else {
            return $this->styles;
        }
    }

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

    public function getPageItemTypeStatic()
    {
        $blockTypes = PageItem::blockTypes();

        if(!$blockTypes->has($this->block_type)) {
            return null;
        }

        return $blockTypes[$this->block_type];
    }

    public function getBackgroundColor()
    {
        return $this->styles?->content?->background_color ?: ($this->getPageItemTypeStatic() ? $this->getPageItemTypeStatic()::getDefaultBackgroundColor($this->page?->team_id, $this->page) : '#ffffff');
    }

    public function getTextColor()
    {
        return $this->styles?->content?->text_color ?: ($this->getPageItemTypeStatic() ? $this->getPageItemTypeStatic()::getDefaultTextColor($this->page?->team_id, $this->page) : '#000000');
    }

    public function getFontSize()
    {
        return $this->styles?->content?->font_size_raw ?: ($this->getPageItemTypeStatic() ? $this->getPageItemTypeStatic()::getDefaultFontSize($this->page?->team_id, $this->page) : 16);
    }

    public function getLinkColor()
    {
        return $this->styles?->content?->link_color ?: ($this->getPageItemTypeStatic() ? $this->getPageItemTypeStatic()::getDefaultLinkColor($this->page?->team_id, $this->page) : '#0000ff');
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

    public function getStyleProperty($property)
    {
        return $this->styles?->content?->$property ?? !$this->getPageItemTypeStatic() ? null : PageItemStyleModel::getGenericStylesOfType($this->getPageItemTypeStatic(), $this->page->team_id)?->content?->$property ?? $this->page->getStyleProperty($property);
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

    /* ACTIONS */
    public function save(array $options = [])
    {
        $this->getPageItemType()?->beforeSave($this);
        $result = parent::save($options);
        $this->getPageItemType()?->afterSave($this);

        return $result;
    }
}
