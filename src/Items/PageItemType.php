<?php

namespace Anonimatrix\PageEditor\Items;

use Anonimatrix\PageEditor\Casts\Style;
use Anonimatrix\PageEditor\Models\PageItemStyle;
use Anonimatrix\PageEditor\Support\Facades\PageItem as PageItemFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class PageItemType
{
    public const ITEM_TAG = 'h1';
    public const ITEM_NAME = 'default';
    public const ITEM_TITLE = 'newsletter.default';
    public const ITEM_DESCRIPTION = 'newsletter.a-default-page-item';
    public const SPECIFIC_GROUP = '';

    protected string | object $content;
    protected string $classes;
    protected string|Style $styles;
    protected Model $pageItem;
    protected $variables = [];

    protected $editPanelId = '';

    // Forms fields names
    protected $nameTitle = 'title';
    protected $nameContent = 'content';
    protected $nameImage = 'image';

    protected $valueTitle = '';
    protected $valueContent = '';
    protected $valueImage = '';

    protected $interactsWithPageItem = true;

    public const DISABLE_AUTO_STYLES = false;

    public const ONLY_CUSTOM_STYLES = false;

    public function __construct(Model $pageItem, $interactsWithPageItem = true)
    {
        $this->content = $pageItem?->content ?: '';

        $this->styles = $this->defaultStyles($pageItem);
        $this->classes = $this->defaultClasses($pageItem);

        $this->classes .= $pageItem?->classes ?: '';
        $this->styles .= $pageItem?->styles?->content ?: '';

        $this->pageItem = $pageItem;

        $this->styles = new Style($this->styles);

        $this->interactsWithPageItem = $interactsWithPageItem;
    }

    /** VARIABLES */
    public function setVariables($variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /** CONVERTION */

    /**
     * Convert the item to html.
     * @return string
     */
    abstract public function toHtml(): string;

    final public function toHtmlWrap(): string {
        return $this->getGridSteblingsHtml($this->toHtml());
    }

    /**
     * Convert the item to a kompo element.
     * @return \Kompo\Elements\Element
     */
    abstract protected function toElement($withEditor = null);

    final protected function toElementWithStyles($withEditor = null)
    {
        if(static::DISABLE_AUTO_STYLES) return $this->toElement($withEditor);

        return $this->toElement($withEditor)?->style((string) $this->styles)?->class($this->classes);
    }

    final public function toElementWrap($withEditor = null)
    {
        return $this->getGridSteblingsElement(
            $this->toElementWithStyles($withEditor),
            $withEditor,
        );
    }

    /** PREVIEW OPTIONS */
    /**
     * Convert the item to a preview kompo element.
     * @return \Kompo\Elements\Element
     */
    public function toPreviewElement($withEditor = false)
    {
        $items = collect([$this->pageItem, ...$this->pageItem->pageItems]);

        if ($items->count() > 1) {
            return _Columns(
                $items->map(fn($item) => $this->toPreviewSinglePageItem($item, $withEditor))
            );
        }

        return $this->toPreviewSinglePageItem($items->first(), $withEditor);
    }

    protected function toPreviewSinglePageItem($item, $withEditor = false)
    {
        $itemType = $item->getPageItemType();
        $itemType->setVariables($this->variables);
        $el = $itemType?->toElementWithStyles($withEditor);

        return !$withEditor ? $el : _Flex(
            $itemType?->adminPreviewOptions($this->editPanelId),
            _Rows($el)
                ->class('border-2 border-dashed box-content border-gray-300 hover:border-blue-600 w-full py-1 px-2')
                ->selfGet('getPageItemForm', ['item_id' => $item->id, 'page_id' => $item->page->id])
                ->inPanel($this->editPanelId),
                // ->run('() => {if(document.querySelector(".kompoScrollableContent")) { document.querySelector(".kompoScrollableContent").scrollTop = 0;} }'),
        )->class('group relative mb-3 mt-10')->style('flex-grow: 1');
    }

    /**
     * Get the admin options for the preview.
     */
    public function adminPreviewOptions($editPanelId = '')
    {
        return _Flex(
            $this->moveOrderButton($editPanelId),
            $this->actionsButtons($editPanelId),
        )->class('px-1 hidden -top-[34px] -z-index-1 group-hover:flex absolute align-between justify-between gap-2');
    }

    /**
     * Get the admin buttons for the preview.
     */
    public function actionsButtons($editPanelId = '')
    {
        $canSwitch = $this->pageItem->page_item_id && !$this->pageItem->pageItems()->count();
        $canAddColumn = !$this->pageItem->page_item_id;

        return $this->adminButtonsGroup(
            [
                !$canSwitch ? null :
                    _Link()->icon('arrows')->balloon('newsletter.switch-columns', 'down-right')
                    ->selfPost('switchColumnOrder', ['id' => $this->pageItem->id])
                    ->refresh(),
                !$canAddColumn ? null :
                    _Link()->icon('columns')->balloon('newsletter.add-column', 'down-right')
                    ->selfPost('addPageItemColumn', ['id' => $this->pageItem->id])
                    ->refresh(),
                /*_Link()->icon('pencil-alt')->balloon('Edit block', 'down-right')
                    ->selfGet('getPageItemForm', ['item_id' => $this->pageItem->id, 'page_id' => $this->pageItem->page_id])
                    ->inPanel($editPanelId),*/
                _DeleteLink()->icon('trash')->byKey($this->pageItem)->browse()->balloon('newsletter.delete', 'down-right'),
            ]
        );
    }

    /**
     * Get the admin button to move the order of the item.
     */
    public function moveOrderButton($editPanelId = '')
    {
        return $this->adminButtonsGroup(
            [_Html()->icon('selector')->class('cursor-move')],
        );
    }

    /**
     * Generate a group of buttons for the admin preview.
     */
    public function adminButtonsGroup($els = [])
    {
        $els = collect($els);

        $btnClass = 'px-4 py-2 text-lg text-gray-700 block flex justify-center items-center bg-gray-300 rounded-t';
        $btnGroupClass = 'flex gap-3';

        return _Flex(
            $els->map(fn ($el) => $el?->class($btnClass)),
        )->class($btnGroupClass);
    }

    /** KOMPO ELEMENTS */

    /**
     * Get kompo element for the page item edition.
     * @return \Kompo\Elements\Element
     */
    abstract public function blockTypeEditorElement();

    /**
     * Get kompo element for the page item edition styles.
     * @return \Kompo\Elements\Element|null
     */
    public function blockTypeEditorStylesElement()
    {
        return null;
    }

    /**
     * Get kompo element for the page item presentation.
     * @return \Kompo\Elements\Element
     */
    public static function blockTypeElement()
    {
        return _Rows(
            _Html(static::ITEM_TITLE)->class('font-medium mb-2'),
            _Html(static::ITEM_DESCRIPTION)->class('text-sm text-gray-400')
        )->class('p-4');
    }

    /** UTILS */

    /**
     * Helper to get html tag with the content inside.
     * @return string
     */
    final protected function openCloseTag($content = ''): string
    {
        if (!$this::ITEM_TAG) throw new \Exception('ITEM_TAG not defined in ' . static::class);

        return '<' . $this::ITEM_TAG . ' class="' . $this->classes . '" style="' . $this->styles . '">' . ($content ?: $this->content) . '</' . $this::ITEM_TAG . '>';
    }

    protected function getGridSteblingsElement($el, $withEditor = null)
    {
        $gridSteblings = $this->pageItem->pageItems()->count() > 0 ? $this->pageItem->pageItems : null;

        if (!$gridSteblings) {
            return $el;
        }

        return _Flex(
            $el->style('flex-grow: 1'),
            ...$gridSteblings->map(function ($el) use ($withEditor) {
                return $el->getPageItemType()?->toElementWrap($withEditor)?->style('flex-grow: 1');
            }),
        );
    }

    protected function getGridSteblingsHtml($html)
    {
        $gridSteblings = $this->pageItem->pageItems()->count() > 0 ? $this->pageItem->pageItems : null;

        if (!$gridSteblings) {
            return $html;
        }

        return '<div style="display: flex; justify-content:center; align-items: center;">' . $html . $gridSteblings->map(function ($el) {
            return $el->getPageItemType()?->toHtmlWrap();
        })->join('') . '</div>';
    }

    /** STYLES */

    /**
     * Get the default classes for the item.
     * @return string
     */
    public function defaultClasses($pageItem): string
    {
        return '';
    }

    /**
     * Get the default styles for the item.
     * @return string
     */
    public function defaultStyles($pageItem): string
    {
        return static::defaultGenericStyles($pageItem->page?->team?->id) ?? '';
    }

    public static function defaultGenericStyles($teamId = null): string|Style
    {
        return PageItemStyle::getGenericStylesOfType(static::class, $teamId)->content ?? '';
    }

    protected function overrideStyles($styles, $withDefault = false)
    {
        if ($withDefault) {
            $styles = $this->defaultStyles($this->pageItem) . $styles;
        }

        $this->styles = new Style($styles);

        return $this;
    }

    public static function getDefaultBackgroundColor($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->background_color ?? $page?->getContentBackgroundColor() ?? '#ffffff';
    }

    public static function getDefaultTextColor($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->color ?? $page?->getTextColor() ??  '#000000';
    }

    public static function getDefaultFontSize($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->font_size_raw ?? $page?->getFontSize() ?? 12;
    }

    public static function getDefaultLinkColor($teamId = null, $page = null)
    {
        return static::defaultGenericStyles($teamId)?->link_color ?? $page?->getLinkColor() ?? '#2443e0';
    }

    /* STYLES ELEMENTS */
    protected function justifyStylesEls()
    {
        return _Rows(
            _ButtonGroup('newsletter.page-item-justify')->class('mt-4')->name('align-items', false)->options([
                'start' => __('cms::cms.left'),
                'center' => __('cms::cms.center'),
                'end' => __('cms::cms.right'),
            ])->optionClass('px-4 py-2 text-center cursor-pointer')
            ->selectedClass('bg-level3 text-white font-medium', 'bg-gray-200 text-level3 font-medium')
            ->value($this->styles->align_items ?: 'center'),
        );
    }

    protected function borderWidthsStylesEls()
    {
        return _Columns(
            _Input()->placeholder('cms::cms.border-top')->name('border-top-width', false)->default($this->styles?->border_top_width_raw)->class('whiteField'),
            _Input()->placeholder('cms::cms.border-right')->name('border-right-width', false)->default($this->styles?->border_right_width_raw)->class('whiteField'),
            _Input()->placeholder('cms::cms.border-bottom')->name('border-bottom-width', false)->default($this->styles?->border_bottom_width_raw)->class('whiteField'),
            _Input()->placeholder('cms::cms.border-left')->name('border-left-width', false)->default($this->styles?->border_left_width_raw)->class('whiteField'),
        );
    }

    /** AUTHORIZATION */

    /**
     * Get the kompo element for the page item edition.
     * @return array<boolean, string>
     */
    public function authorize()
    {
        return [
            'create' => [PageItemFacade::authorize('create'), 'auth.you-are-not-authorized-to-create-this-item-type'],
            'update' => [PageItemFacade::authorize('update'), 'auth.you-are-not-authorized-to-update-this-item-type'],
            'delete' => [PageItemFacade::authorize('delete'), 'auth.you-are-not-authorized-to-delete-this-item-type'],
        ];
    }

    /**
     * Check if the user can perform the action on the item.
     * @param  string $action
     * @return object {authorized: boolean, message: string}
     */
    final public function can($action)
    {
        $authRes = $this->authorize()[$action];

        $can = false;
        $message = '';

        if (is_bool($authRes)) {
            $can = $authRes;
            $message = !$can ? 'auth.you-are-not-authorized-to' . ' ' . $action . ' ' . 'auth.this-item-type' : '';
        } else if (is_array($authRes)) {
            $can = $authRes[0];
            $message = !$can ? $authRes[1] : '';
        }

        return (object) [
            'authorized' => $can,
            'message' => $message,
        ];
    }

    /**
     * Works like second validation before saving the page item. PageItemForm validation is the first one.
     */
    public function validate()
    {
        $attrs = $this->pageItem->getAttributes();

        $formattedAttrs = [
            'image' => $attrs['image'] ?? null,
            'title' => $attrs['title'] ?? null,
            'content' => collect(json_decode($attrs['content']))->first(), // Temporary
        ];

        $validator = Validator::make($formattedAttrs, $this->rules());

        if ($validator->fails()) {
            $element = collect($validator->errors()->getMessages())->take(1);
            throwValidationError($element->keys()->first(), $element->first()[0]);
            throw new HttpException(500, "Validation error", null, $validator->errors()->getMessages()); // Logging the error
        }

        return $this;
    }

    public function rules()
    {
        return [];
    }

    /* SETTERS */
    public function setEditPanelId($id)
    {
        $this->editPanelId = $id;

        return $this;
    }

    /* OBSERVERS */
    /**
     * Called before the page item is saved.
     * @return void
     */
    public function beforeSave($model = null) {

    }


    /**
     * Called before the page item is saved.
     * @return void
     */
    public function afterSave($model = null) {}

    /**
     * Called before the page item is mounted in a group. Before toHtml or toElement.
     */
    public function beforeMountInGroup($groupItem) {}

    /* TABLES HTML HELPERS */
    protected function alignElement($el, $align = 'center', $styles = '')
    {
        return '<table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="' . $align . '" style="'. $styles .'">
                    ' . $el . '
                </td>
            </tr>
        </table>';
    }

    protected function centerElement($el)
    {
        return $this->alignElement($el, 'center');
    }

    /* FORM ATTRIBUTES */
    public function setFormNames($title, $content, $image)
    {
        $this->nameTitle = $title;
        $this->nameContent = $content;
        $this->nameImage = $image;

        return $this;
    }

    public function setFormValues($title, $content, $image)
    {
        $this->valueTitle = $title;
        $this->valueContent = $content;
        $this->valueImage = $image;

        return $this;
    }

    public function setPrefixFormNames($prefix)
    {
        $this->nameTitle = $prefix . $this->nameTitle;
        $this->nameContent = $prefix . $this->nameContent;
        $this->nameImage = $prefix . $this->nameImage;

        return $this;
    }
}
