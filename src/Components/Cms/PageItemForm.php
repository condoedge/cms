<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageItemForm extends Form
{
    protected $refresh = true;
    protected $pageId;
    protected $updateOrder;
    public const ITEM_FORM_PANEL_ID = 'itemFormPanel';
    public const ITEM_FORM_STYLES_ID = 'itemFormStyles';
    public const COPY_BLOCK_PANEL_ID = 'copyBlockPanel';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageItemModel::find($this->modelKey()) ?? PageItemModel::make());

        $this->updateOrder = $this->prop('update_order');

        $this->pageId = $this->prop('page_id');
        $this->model->page_id = $this->pageId;

        $this->model->block_type = $this->model->block_type ?: request('block_type');
    }

    public function beforeSave()
    {
        if ($this->updateOrder) {
            $this->model->order = $this->model->page->pageItems()->count() - 1;
        }

        $this->model->title = request('title');
        $this->model->content = request('content');
    }

    public function afterSave()
    {
        $styleModel = $this->model->styles ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $styleModel->content .= request('styles');

        $this->model->styles()->save($styleModel);
    }

    public function render()
    {
        $types = PageEditor::getOptionsTypes($this->prefixGroup);

        // New block creation — show block type selector
        if (!$this->model->id && !$this->model->block_type) {
            return $this->blockTypeSelector($types);
        }

        // Existing block — show unified property panel
        return $this->unifiedPropertyPanel();
    }

    protected function blockTypeSelector($types)
    {
        if (!$this->model->id) {
            $types = $types + ['__copy__' => __('cms::cms.copy-block-from-newsletter')];
        }

        return _Rows(
            _Html('cms::cms.add-block')->class('font-semibold text-sm mb-3'),
            _Select('cms::cms.block-type')->options($types)
                ->name('block_type')
                ->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID)
                    && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form')
                    && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID)
                    && $e->selfGet('getCopyBlockPanel')->inPanel(static::COPY_BLOCK_PANEL_ID)
                ),
            !$this->model->id ? _Panel()->id(static::COPY_BLOCK_PANEL_ID)->class('mt-4') : null,
            _Panel(
                $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : null,
            )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
            _Panel(
                $this->getStyleFormComponent(),
            )->id('item_styles_form')->class('mt-2'),
            _Panel()->id(static::ITEM_FORM_STYLES_ID),
            $this->saveButtons(),
        )->class('p-4');
    }

    protected function unifiedPropertyPanel()
    {
        $blockType = $this->model->getPageItemType();
        $icon = $blockType ? (defined($blockType::class.'::ITEM_ICON') ? $blockType::ITEM_ICON : 'document-text') : 'document-text';
        $title = $blockType ? __($blockType::ITEM_TITLE) : '';

        return _Rows(
            // Block type header with title input
            _Rows(
                _FlexBetween(
                    _Flex(
                        _Html()->icon(_Sax($icon, 20))->class('text-blue-600'),
                        _Html($title)->class('font-semibold text-sm'),
                    )->class('items-center gap-2'),
                    $this->model->id ? _Link()->icon('x')->class('text-gray-400 hover:text-gray-600 p-1')
                        ->run('() => {
                            document.querySelectorAll(".vlEmailBlock").forEach(b => b.classList.remove("vlEmailBlockSelected"));
                            document.getElementById("'.EmailEditorLayout::PROPERTY_PANEL.'").innerHTML = "<div class=\"flex flex-col items-center justify-center py-20\"><div class=\"text-gray-300 mb-4\"><svg width=\"48\" height=\"48\"><use href=\"#mouse-circle\"/></svg></div><div class=\"text-sm text-gray-400 text-center\">'.__('cms::cms.select-block-to-edit').'</div></div>";
                        }') : null,
                )->class('mb-3'),
                _Hidden()->name('block_type')->value($this->model->block_type),
                _Input('cms::cms.title-optional')->name('name_pi'),
            )->class('vlPropertyHeader vlPropertySection mb-4'),

            // Content section
            _Rows(
                $blockType ? $blockType->blockTypeEditorElement() : null,
            )->class('vlPropertySection vlPropertySectionBody mb-4'),

            // Style section (colors, typography, spacing, responsive, advanced — all inside StylePageItemForm)
            _Rows(
                _Html('cms::cms.style')->class('vlPropertySectionTitle'),
                _Rows(
                    _Panel(
                        $this->getStyleFormComponent(),
                    )->id('item_styles_form'),
                    _Panel()->id(static::ITEM_FORM_STYLES_ID),
                )->class('vlPropertySectionBody'),
            )->class('vlPropertySection'),

            // Action buttons
            $this->saveButtons(),

            // Styles for property panel
            _Html($this->propertyPanelStyles()),
        )->class('vlPropertyPanel');
    }

    protected function saveButtons()
    {
        $previewPanel = EmailEditorLayout::PREVIEW_PANEL;

        return _Rows(
            _SubmitButton('cms::cms.save')->class('vlPropertySaveBtn w-full')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel)
                    && $e->run('() => { if (window.vlEmailEditor) vlEmailEditor.showToast("'.__('cms::cms.saved-successfully').'") }')
                ),
            $this->model->id ? _DeleteButton('cms::cms.delete-block')
                ->byKey($this->model)
                ->class('vlPropertyDeleteBtn w-full mt-2')
                ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel($previewPanel)) : null,
        )->class('vlPropertyActions');
    }

    protected function propertyPanelStyles()
    {
        return '<style>
            .vlPropertyPanel {
                padding: 0;
            }
            .vlPropertyHeader {
                padding: 16px 32px;
                border-bottom: 1px solid #e5e7eb;
                position: sticky;
                top: 0;
                background: #ffffff;
                z-index: 5;
            }
            .vlPropertySection {
                border-bottom: 1px solid #f3f4f6;
            }
            .vlPropertySectionTitle {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6b7280;
                padding: 14px 32px 8px;
            }
            .vlPropertySectionBody {
                padding: 0 32px 16px;
            }
            .vlPropertyActions {
                padding: 16px 32px;
                position: sticky;
                bottom: 0;
                background: #ffffff;
                border-top: 1px solid #e5e7eb;
            }
            .vlPropertySaveBtn {
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
                font-size: 13px !important;
                padding: 14px !important;
            }
            .vlPropertySaveBtn:hover {
                background: #1d4ed8 !important;
            }
            .vlPropertyDeleteBtn {
                background: transparent !important;
                color: #dc2626 !important;
                border: 1px solid #fecaca !important;
                border-radius: 8px !important;
                font-size: 13px !important;
                padding: 14px !important;
            }
            .vlPropertyDeleteBtn:hover {
                background: #fef2f2 !important;
            }
        </style>';
    }

    public function rules()
    {
        $itemRules = !$this->model->block_type ? [] : ($this->model->getPageItemType()?->rules() ?? []);

        return [
            'block_type' => 'required',
            ...$itemRules,
        ];
    }

    public function refreshItemForm()
    {
        return PageEditor::getPageItemFormComponent($this->prefixGroup, null, [
            'update_order' => true,
            'page_id' => $this->pageId,
        ]);
    }

    public function getPagePreview()
    {
        return PageEditor::getPagePreviewComponent(
            $this->prefixGroup,
            [
                'page_id' => $this->pageId,
                'panel_id' => EmailEditorLayout::PROPERTY_PANEL,
                'with_editor' => true
            ]
        );
    }

    public function getStyleFormComponent()
    {
        return PageEditor::getItemStylesFormComponent($this->prefixGroup, $this->model->id, [
            'page_id' => $this->pageId,
            'block_type' => request('block_type') ?? $this->model->block_type,
        ]);
    }

    public function setGenericStyles()
    {
        if (!$this->model->getPageItemType()) return;

        $styleModel = PageItemStyleModel::getGenericStylesOfType($this->model->getPageItemType()::class, $this->model->page?->team_id) ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $styleModel->block_type = request('block_type');
        $styleModel->save();
    }

    public function itemForm()
    {
        if(request('block_type') === '__copy__' || !$this->isValidBlockType()) {
            return _Rows();
        }

        $item = PageItemModel::blockTypes()[request('block_type')];
        $item = new $item($this->model);

        return _Rows(
            $item->blockTypeEditorElement(),
        );
    }

    public function itemStylesForm()
    {
        if(!$this->isValidBlockType()) {
            return _Rows();
        }

        $item = PageItemModel::blockTypes()[request('block_type')];
        $item = new $item($this->model);

        return !$item->blockTypeEditorStylesElement() ? null : _Rows(
            _Html('cms::cms.styles-for-item')->class('text-sm font-semibold mb-1'),
            $item->blockTypeEditorStylesElement(),
        );
    }

    protected function isValidBlockType($blockType = null)
    {
        $blockType = $blockType ?? request('block_type');

        return $blockType && PageItemModel::blockTypes()->has($blockType);
    }

    public function getCopyBlockPanel()
    {
        if (request('block_type') !== '__copy__') {
            return _Html('');
        }

        $currentPage = PageModel::find($this->pageId);
        $query = PageModel::where('id', '!=', $this->pageId);

        if ($currentPage?->team_id) {
            $query->where('team_id', $currentPage->team_id);
        }

        $pages = $query->orderByDesc('updated_at')
            ->get()
            ->mapWithKeys(fn($page) => [$page->id => $page->title]);

        return _Rows(
            _Select('cms::cms.select-newsletter')->name('select_newsletter', false)->options($pages->toArray())
                ->onChange(fn($e) => $e->selfGet('getPageBlocksSelect')->inPanel('copyBlockItemsPanel')),
            _Panel(
                _Html(''),
            )->id('copyBlockItemsPanel'),
        );
    }

    public function getPageBlocksSelect()
    {
        $pageId = request('select_newsletter');

        if (!$pageId) {
            return _Html('');
        }

        $page = PageModel::findOrFail($pageId);
        $items = $page->orderedMainPageItems()->get();

        $options = $items->mapWithKeys(function ($item) {
            $type = $item->getPageItemType();
            $typeName = $type ? __($type::ITEM_TITLE) : '';
            $zoneName = $item->name_pi ?: $typeName;
            $label = $zoneName . ($item->name_pi ? ' (' . $typeName . ')' : '');
            return [$item->id => $label];
        });

        return _Rows(
            _Select('cms::cms.select-block')->name('select_block', false)->options($options->toArray())
                ->onChange(fn($e) => $e->selfGet('getCopyButton')->inPanel('copyBlockButtonPanel')),
            _Panel(
                _Html(''),
            )->id('copyBlockButtonPanel'),
        );
    }

    public function getCopyButton()
    {
        $itemId = request('select_block');

        if (!$itemId) {
            return _Html('');
        }

        return _Button('cms::cms.copy-this-block')->icon('duplicate')
            ->selfPost('copyBlockToPage', ['item_id' => $itemId])
            ->onSuccess(fn($e) => $e->selfGet('refreshItemForm')->inPanel(EmailEditorLayout::PROPERTY_PANEL) && $e->selfGet('getPagePreview')->inPanel(EmailEditorLayout::PREVIEW_PANEL))
            ->class('mt-2');
    }

    public function copyBlockToPage()
    {
        $sourceItem = PageItemModel::findOrFail(request('item_id'));
        $page = PageModel::findOrFail($this->pageId);

        $newItem = $sourceItem->replicate();
        $newItem->page_id = $this->pageId;
        $newItem->order = $page->pageItems()->count();
        $newItem->page_item_id = null;
        $newItem->group_page_item_id = null;
        $newItem->save(['skip_validation' => true]);

        if ($sourceItem->styles) {
            $newStyles = $sourceItem->styles->replicate();
            $newItem->styles()->save($newStyles);
        }

        $sourceItem->groupPageItems()->each(function ($groupItem) use ($newItem) {
            $newGroupItem = $groupItem->replicate();
            $newGroupItem->group_page_item_id = $newItem->id;
            $newGroupItem->page_id = $newItem->page_id;
            $newGroupItem->save(['skip_validation' => true]);

            if ($groupItem->styles) {
                $newGroupStyles = $groupItem->styles->replicate();
                $newGroupItem->styles()->save($newGroupStyles);
            }
        });
    }
}
