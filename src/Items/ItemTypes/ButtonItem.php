<?php

namespace Anonimatrix\PageEditor\Items\ItemTypes;

use Anonimatrix\PageEditor\Casts\Style;
use Anonimatrix\PageEditor\Items\PageItemType;
use Anonimatrix\PageEditor\Models\PageItem;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;

class ButtonItem extends PageItemType
{
    public const ITEM_TAG = 'a';
    public const ITEM_NAME = 'button';
    public const ITEM_TITLE = 'cms::cms.items.button';
    public const ITEM_DESCRIPTION = 'cms::cms.items.button';
    public const ITEM_ICON = 'mouse-square';

    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';
    public const SIZE_FULL = 'full';

    public function __construct(PageItem $pageItem, $interactsWithPageItem = true)
    {
        parent::__construct($pageItem, $interactsWithPageItem);

        $this->content = (object) [
            'title' => $pageItem->title,
            'href' => $pageItem->content,
        ];
    }

    public function blockTypeEditorElement()
    {
        $buttonTitleEl = _Translatable('newsletter.button-title')->name($this->nameTitle, $this->interactsWithPageItem);
        $buttonHrefEl = _Translatable('newsletter.button-href')->name($this->nameContent, $this->interactsWithPageItem);

        if($this->valueTitle) $buttonTitleEl = $buttonTitleEl->default(json_decode($this->valueTitle));
        if($this->valueContent) $buttonHrefEl = $buttonHrefEl->default(json_decode($this->valueContent));

        return _Columns(
            $buttonTitleEl,
            $buttonHrefEl,
        );
    }

    public function blockTypeEditorStylesElement()
    {
        return _Rows(
            _Select('cms::cms.button-size')->name($this->formPrefix . 'button-size', false)
                ->options(static::getSizeOptions())
                ->default($this->styles->button_size ?: static::SIZE_MEDIUM)
                ->class('whiteField'),
        );
    }

    public function afterSave($model = null)
    {
        parent::afterSave($model);

        $styleModel = $this->pageItem->getOrCreateStyles();

        if ($this->interactsWithPageItem) {
            PageStyle::setStylesToModel($styleModel);
        } else {
            $this->saveButtonStylesToModel($styleModel);
        }

        $styleModel->save();
    }

    protected function saveButtonStylesToModel($styleModel)
    {
        $value = request($this->formPrefix . 'button-size');

        if (!is_null($value)) {
            $suffix = config('page-editor.automapping_styles.button-size', '');
            $styleModel->content->replaceProperty('button-size', $value . $suffix);
        }
    }

    public static function getSizeOptions(): array
    {
        return [
            static::SIZE_SMALL => __('cms::cms.button-size-small'),
            static::SIZE_MEDIUM => __('cms::cms.button-size-medium'),
            static::SIZE_LARGE => __('cms::cms.button-size-large'),
            static::SIZE_FULL => __('cms::cms.button-size-full'),
        ];
    }

    protected function getButtonWidth(): string
    {
        $size = $this->styles->button_size ?: static::SIZE_MEDIUM;

        return match($size) {
            static::SIZE_SMALL => '20%',
            static::SIZE_MEDIUM => '30%',
            static::SIZE_LARGE => '50%',
            static::SIZE_FULL => '100%',
            default => '30%',
        };
    }

    protected function toElement($withEditor = null)
    {
        return !$this->content->href || !$this->content->title ? null : _Link($this->content->title)
            ->target('_blank')
            ->href($this->content->href);
    }

    public function toHtml(): string
    {
        if (!$this->content->href || !$this->content->title) {
            return '';
        }

        $buttonWidthPx = $this->getButtonWidthPx();
        $borderRadius = (int) ($this->styles->border_radius_raw ?: 5);

        return view('cms::items.button', [
            'href' => htmlspecialchars($this->content->href, ENT_QUOTES),
            'title' => htmlspecialchars($this->content->title, ENT_QUOTES),
            'bgColor' => $this->styles->background_color ?: '#2563eb',
            'textColor' => $this->styles->color ?: '#ffffff',
            'borderRadius' => $borderRadius,
            'fontSize' => (int) ($this->styles->font_size_raw ?: 14),
            'padding' => $this->styles->padding ?: '15px 4px',
            'buttonWidth' => $this->getButtonWidth(),
            'buttonWidthPx' => $buttonWidthPx,
            'arcsize' => $buttonWidthPx > 0 ? (int) round(($borderRadius / $buttonWidthPx) * 100) : 10,
        ])->render();
    }

    /**
     * Get the button width in pixels (based on a 600px container).
     */
    protected function getButtonWidthPx(): int
    {
        $pct = (int) str_replace('%', '', $this->getButtonWidth());

        return (int) round(600 * $pct / 100);
    }

    public function rules()
    {
        return [
            'title' => 'required',
            'content' => 'required',
        ];
    }

    public function defaultClasses($pageItem): string
    {
        return '';
    }

    public function defaultStyles($pageItem): string
    {
        $styles = parent::defaultStyles($pageItem);
        $styles .= 'text-align: center !important; padding: 15px 4px !important; margin: 10px auto !important; color: white !important; display: inline-block; font-weight: 600; border-radius: 5px; min-width: 200px; text-decoration: none;';

        $styles .= 'background: ' . $pageItem->styles?->background_color . '!important;';
        $this->styles = new Style($pageItem->styles->content ?? ''); // Minor fix to be able to get button width
        $styles .= 'width: ' . ($this->getButtonWidth() ?? '30%') . ' !important;';

        return $styles;
    }

    public static function getDefaultFontSize($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->font_size_raw ?? 14;
    }
}
