<?php

namespace Anonimatrix\PageEditor\Items;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Illuminate\Database\Eloquent\Model;
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

    public function __construct(Model $pageItem)
    {
        parent::__construct($pageItem);

        $this->groupItems = $this->pageItem->groupPageItems()->get();

        $this->groupItemsStyles = collect(static::GROUP_ITEMS_TYPES)->mapWithKeys(function($groupItemType){
            return [$groupItemType => $this->defaultParentStylesConstructor()];
        })->all();
    }

    public function beforeSave($model = null)
    {}

    public function afterSave($model = null)
    {
        if(!$model) return;

        $groupItemsToSave = collect(static::GROUP_ITEMS_TYPES)->map(function($groupItemType, $i) use ($model) {
            $title = request($i . '_title');
            $content = request($i . '_content');
            $image = request($i . '_image');

            $item = $this->groupItems[$i] ?? PageItemModel::make();

            $instance = new $groupItemType($item, false);

            $item->title = $title;
            $item->content = $content;
            $item->block_type = $groupItemType::ITEM_NAME;
            $item->order = $i;
            $item->page_id = $model->page_id;
            // $item->styles = (static::GROUP_ITEMS_STYLES[$groupItemType] ?? '') . ';';

            if($image && $image instanceof UploadedFile) {
                $item->manualUploadImage($image, 'image_preview', 800);
                $item->manualUploadImage($image, 'image', 1600);
            }

            $instance->setPrefixFormNames($i . '_');
            $instance->beforeSave($item);

            return $item;
        });

        $this->groupItems = $model->groupPageItems()->saveMany($groupItemsToSave);

        collect(static::GROUP_ITEMS_TYPES)->map(function ($groupItemType, $i) use ($model) {
            $item = $this->groupItems[$i];

            $instance = new $groupItemType($item, false);

            $instance->setPrefixFormNames($i . '_');

            $instance->afterSave($item);

            $this->saveSubItemPadding($item, $i);
        });
    }

    public function blockTypeEditorElement()
    {
        $sections = collect(static::GROUP_ITEMS_TYPES)->map(function ($groupItemType, $i) {
            $actualItem = $this->groupItems->first(fn ($item) => $item->order == $i) ?? null;

            $instance = new $groupItemType($actualItem ?? $this->pageItem, false);
            $instance->setPrefixFormNames($i . '_');

            if ($actualItem) {
                $attrs = $actualItem?->getAttributes();
                $instance->setFormValues($attrs['title'], $attrs['content'], $actualItem->image);
            }

            return $this->collapsibleSection(
                __($groupItemType::ITEM_TITLE),
                _Rows(
                    $instance->blockTypeEditorElement(),
                    $instance->blockTypeEditorStylesElement(),
                    $this->subItemPaddingInputs($i, $actualItem),
                ),
            );
        });

        return _Rows(...$sections);
    }

    protected function subItemPaddingInputs($index, $actualItem = null)
    {
        $prefix = $index . '_';

        return $this->collapsibleSection(
            __('cms::cms.spacing'),
            _Rows(
                $this->subItemPaddingTab($prefix, 'desktop', $actualItem),
                $this->subItemPaddingTab($prefix, 'mobile', $actualItem),
            ),
        )->class('mt-2');
    }

    // Section header styling (uppercase, gray, hover-darken) is conveyed via Tailwind classes on
    // the title element; expand/collapse is owned by the _Collapsible Vue component.
    protected function collapsibleSection(string $title, $body)
    {
        $titleEl = _Flex(
            _Html($title)->class('text-xs font-semibold uppercase tracking-wider text-gray-600'),
        )->class('items-center gap-2 py-2.5 cursor-pointer hover:text-gray-800');

        return _Collapsible($body)
            ->titleLabel($titleEl)
            ->withIcon('chevron-up', 'text-gray-400 text-xs')
            ->class('border-b border-gray-200');
    }

    protected function subItemPaddingTab($prefix, $device, $actualItem = null)
    {
        $suffix = $device === 'mobile' ? '-mobile' : '';
        $styleSuffix = $device === 'mobile' ? '_mobile' : '';
        $defaultVal = $device === 'mobile' ? 0 : null;

        $paddingTop = $actualItem?->getStyleProperty('padding_top' . $styleSuffix . '_raw') ?? $defaultVal;
        $paddingBottom = $actualItem?->getStyleProperty('padding_bottom' . $styleSuffix . '_raw') ?? $defaultVal;
        $paddingLeft = $actualItem?->getStyleProperty('padding_left' . $styleSuffix . '_raw') ?? $defaultVal;
        $paddingRight = $actualItem?->getStyleProperty('padding_right' . $styleSuffix . '_raw') ?? $defaultVal;

        $label = $device === 'mobile' ? __('cms::cms.mobile') : __('cms::cms.desktop');

        return _Rows(
            _Html($label)->class('vlStyleSubLabel mt-1'),
            _Div(
                _Input()->placeholder('↑')->name($prefix . 'padding-top' . $suffix, false)->default($paddingTop)->class('vlSpacingInput'),
                _Input()->placeholder('↓')->name($prefix . 'padding-bottom' . $suffix, false)->default($paddingBottom)->class('vlSpacingInput'),
                _Input()->placeholder('←')->name($prefix . 'padding-left' . $suffix, false)->default($paddingLeft)->class('vlSpacingInput'),
                _Input()->placeholder('→')->name($prefix . 'padding-right' . $suffix, false)->default($paddingRight)->class('vlSpacingInput'),
            )->class('vlSpacingControl vlSpacingPadding'),
        );
    }

    protected function saveSubItemPadding($item, $index)
    {
        $prefix = $index . '_';
        $paddingStyles = [
            'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
            'padding-top-mobile', 'padding-bottom-mobile', 'padding-left-mobile', 'padding-right-mobile',
        ];

        $styleModel = $item->getOrCreateStyles();
        $changed = false;

        foreach ($paddingStyles as $style) {
            $value = request($prefix . $style);

            if (!is_null($value) && $value !== '') {
                $suffix = config("page-editor.automapping_styles.$style", 'px');
                $styleModel->content->replaceProperty($style, $value . $suffix);
                $changed = true;
            }
        }

        if ($changed) {
            $styleModel->save();
        }
    }

    protected function toElement($withEditor = null)
    {
        return _Rows(
            collect($this->groupItems)->map(function($groupItem, $i) use ($withEditor){
                $itemType = $groupItem->getPageItemType();

                $styles = $this->childItemTypeStyles($groupItem, $itemType);

                $itemType->overrideStyles($styles, true);
                $itemType->beforeMountInGroup($this->pageItem);

                return $itemType?->toElementWithStyles($withEditor);
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
