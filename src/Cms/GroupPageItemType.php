<?php

namespace App\Cms;

use App\Models\Cms\PageItem;
use Illuminate\Http\UploadedFile;

class GroupPageItemType extends PageItemType
{
    public const ITEM_TAG = 'div';
    public const ITEM_NAME = 'newsletter.group';
    public const ITEM_TITLE = 'newsletter.default-group-item';
    public const ITEM_DESCRIPTION = 'newsletter.default-group-item-type-desc';

     /**
      * The list of items in order to render inside the group.
      * @var array<PageItemType>
      */
    protected const GROUP_ITEMS_TYPES = [];

    /**
     * The list of styles to apply to each item in the group.
     * @var array<PageItemType, (string | callable)>
     */
    protected $groupItemsStyles = [];

    protected $groupItems = [];

    public function __construct(PageItem $pageItem)
    {
        parent::__construct($pageItem);

        $this->groupItems = $this->pageItem->groupPageItems;

        $this->groupItemsStyles = collect(static::GROUP_ITEMS_TYPES)->mapWithKeys(function($groupItemType){
            return [$groupItemType => $this->defaultParentStylesConstructor()];
        })->all();
    }

    public function beforeSave($model = null)
    {
        collect(static::GROUP_ITEMS_TYPES)->map(function ($groupItemType, $i) use ($model) {
            $instance = new $groupItemType($this->pageItem, false);

            $instance->setPrefixFormNames($i . '_');

            $instance->beforeSave($model);
        });
    }

    public function afterSave($model = null)
    {
        if(!$model) return;

        $groupItems = collect();

        collect(static::GROUP_ITEMS_TYPES)->map(function($groupItemType, $i) use ($groupItems, $model) {
            $title = request($i . '_title');
            $content = request($i . '_content');
            $image = request($i . '_image');

            $item = $this->groupItems[$i] ?? new PageItem();

            $item->title = $title;
            $item->content = $content;
            $item->block_type = $groupItemType::ITEM_NAME;
            $item->order = $i;
            $item->page_id = $model->page_id;
            // $item->styles = (static::GROUP_ITEMS_STYLES[$groupItemType] ?? '') . ';';

            if($image && $image instanceof UploadedFile) {
                $item->manualUploadImage($image);
            }

            $groupItems->push($item);
        });

        $model->groupPageItems()->saveMany($groupItems);

        collect(static::GROUP_ITEMS_TYPES)->map(function ($groupItemType) use ($model) {
            $instance = new $groupItemType($this->pageItem, false);

            $instance->setPrefixFormNames('0_');

            $instance->afterSave($model);
        });
    }

    public function blockTypeEditorElement()
    {
       return _Rows(
            collect(static::GROUP_ITEMS_TYPES)->map(function($groupItemType, $i){
                $instance = new $groupItemType($this->pageItem, false);

                $instance->setPrefixFormNames($i . '_');
                $actualItem = $this->groupItems[$i] ?? null;

                if($actualItem) {
                    $attrs = $actualItem?->getAttributes();

                    $instance->setFormValues($attrs['title'], $attrs['content'], $actualItem->image);
                }

                return $instance->blockTypeEditorElement();
            }),
        );
    }

    protected function toElement()
    {
        return _Rows(
            collect($this->groupItems)->map(function($groupItem, $i){
                $itemType = $groupItem->getPageItemType();

                $styles = $this->childItemTypeStyles($groupItem, $itemType);

                $itemType->overrideStyles($styles, true);
                $itemType->beforeMountInGroup($this->pageItem);

                return $itemType?->toElementWithStyles();
            }),
        );
    }

    public function toHtml(): string
    {
        return $this->openCloseTag(
            collect($this->groupItems)->map(function($groupItem){
                $itemType = $groupItem->getPageItemType();

                $styles = $this->childItemTypeStyles($groupItem, $itemType);

                $itemType->overrideStyles($styles, true);
                $itemType->beforeMountInGroup($this->pageItem);

                return $itemType?->toHtml();
            })->join('')
        );
    }

    protected final function childItemTypeStyles($item, $itemType)
    {
        $styles = $this->groupItemsStyles[$itemType::class] ?: '';

        if($styles instanceof \Closure) {
            return $styles($item, $this->pageItem);
        }

        return $styles;
    }

    /**
     * Can be used to construct groupItemStyles for a specific item type.
     */
    protected function defaultParentStylesConstructor($extra = '')
    {
        return function ($pageItem, $parent) use ($extra){
            $styles = $this->defaultStyles($parent);

            return $styles . $extra;
        };
    }
}
