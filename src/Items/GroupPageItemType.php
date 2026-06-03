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

        // Sort by the logical `order` column so render output and indexed
        // lookups (afterSave matches by ->order) stay aligned with the
        // GROUP_ITEMS_TYPES sequence regardless of insertion / id ordering.
        $this->groupItems = $this->pageItem->groupPageItems()
            ->orderBy('order')
            ->get();

        $this->groupItemsStyles = collect(static::GROUP_ITEMS_TYPES)->mapWithKeys(function($groupItemType){
            return [$groupItemType => $this->defaultParentStylesConstructor()];
        })->all();
    }

    public function beforeSave($model = null)
    {}

    protected function cloneUploadedFile(UploadedFile $file): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cms_img_');

        // getRealPath() can return false or a path that's no longer a regular
        // file (e.g. after Intervention's first pass). Guard with is_file()
        // and fall back to streaming the contents via the framework helper.
        $sourcePath = $file->getRealPath();
        if (is_string($sourcePath) && is_file($sourcePath)) {
            copy($sourcePath, $tmp);
        } else {
            file_put_contents($tmp, $file->getContent());
        }

        return new UploadedFile(
            $tmp,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            UPLOAD_ERR_OK,
            true
        );
    }

    public function afterSave($model = null)
    {
        if(!$model) return;

        $groupItemsToSave = collect(static::GROUP_ITEMS_TYPES)->map(function($groupItemType, $i) use ($model) {
            $title = request($i . '_title');
            $content = request($i . '_content');
            $image = request($i . '_image');

            // Match by logical `order` (matches what blockTypeEditorElement uses
            // when rendering). Indexing by Collection position breaks once items
            // get re-ordered, soft-deleted, or inserted in a different sequence
            // than GROUP_ITEMS_TYPES — which is when previously-saved images
            // would silently land on the wrong row and the right row would be
            // replaced by a fresh blank model.
            $item = $this->groupItems->first(fn ($existing) => $existing->order == $i)
                ?? PageItemModel::make();

            $instance = new $groupItemType($item, false);

            $item->title = $title;
            $item->content = $content;
            $item->block_type = $groupItemType::ITEM_NAME;
            $item->order = $i;
            $item->page_id = $model->page_id;

            $hasNewUpload = $image instanceof UploadedFile && $image->isValid();

            if ($hasNewUpload) {
                // Snapshot the upload to a fresh temp file for each call.
                // Intervention/GD reads the tmp file in the first call, after
                // which the UploadedFile's internal state becomes unreliable.
                $previewCopy = $this->cloneUploadedFile($image);
                $fullCopy = $this->cloneUploadedFile($image);
                $item->manualUploadImage($previewCopy, 'image_preview', 800);
                $item->manualUploadImage($fullCopy, 'image', $item->getNewsletterMaxImageWidth());
            }
            // No new upload -> keep whatever image/image_preview were already on
            // the model. Eloquent's dirty tracking handles this naturally because
            // we never touched those attributes above.

            $instance->setPrefixFormNames($i . '_');
            $instance->beforeSave($item);

            return $item;
        });

        $this->groupItems = $model->groupPageItems()->saveMany($groupItemsToSave);

        collect(static::GROUP_ITEMS_TYPES)->map(function ($groupItemType, $i) use ($model) {
            // Same order-based lookup as above so the post-save hooks operate on
            // the same row we just wrote, not an arbitrary Collection position.
            $item = $this->groupItems->first(fn ($existing) => $existing->order == $i);
            if (!$item) return;

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

            $icon = defined($groupItemType.'::ITEM_ICON') ? $groupItemType::ITEM_ICON : 'document-text';

            return $this->collapsibleSection(
                __($groupItemType::ITEM_TITLE),
                _Rows(
                    $instance->blockTypeEditorElement(),
                    $instance->blockTypeEditorStylesElement(),
                    $this->subSectionDivider(__('cms::cms.spacing')),
                    $this->subItemPaddingTabs($i, $actualItem),
                ),
                $icon,
            );
        });

        return _Rows(...$sections);
    }

    protected function subItemPaddingTabs($index, $actualItem = null)
    {
        $prefix = $index . '_';

        return _Tabs(
            _Tab(
                $this->subItemPaddingTab($prefix, 'desktop', $actualItem),
            )->label('cms::cms.desktop')->class('vlSpacingTabContent'),
            _Tab(
                $this->subItemPaddingTab($prefix, 'mobile', $actualItem),
            )->label('cms::cms.mobile')->class('vlSpacingTabContent'),
        )->class('vlSpacingTabs');
    }

    protected function collapsibleSection(string $title, $body, ?string $icon = null)
    {
        return _PageEditorSection($title, $body, $icon);
    }

    // Inline divider with a small label, used inside a card to mark the start of a sub-section
    // (e.g. "Spacing") without nesting another collapsible.
    protected function subSectionDivider(string $label)
    {
        return _Html($label)
            ->class('text-xs font-semibold uppercase tracking-wider text-gray-500 mt-8 mb-4 pt-6 border-t border-gray-200');
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

        return _Rows(
            _Html('cms::cms.padding-px')->class('vlStyleSubLabel'),
            _Div(
                _Input()->placeholder('cms::cms.spacing-top')->name($prefix . 'padding-top' . $suffix, false)->default($paddingTop)->class('vlSpacingInput'),
                _Input()->placeholder('cms::cms.spacing-bottom')->name($prefix . 'padding-bottom' . $suffix, false)->default($paddingBottom)->class('vlSpacingInput'),
                _Input()->placeholder('cms::cms.spacing-left')->name($prefix . 'padding-left' . $suffix, false)->default($paddingLeft)->class('vlSpacingInput'),
                _Input()->placeholder('cms::cms.spacing-right')->name($prefix . 'padding-right' . $suffix, false)->default($paddingRight)->class('vlSpacingInput'),
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
