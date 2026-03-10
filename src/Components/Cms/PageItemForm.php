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

        if (!$this->model->id) {
            $types = $types + ['__copy__' => __('cms::cms.copy-block-from-newsletter')];
        }

        return _Tabs(
            _Tab(
                _Rows(
                    _Columns(
                        _Select('cms::cms.zone-type')->options(
                            $types,
                        )->name('block_type')->onChange(fn($e) => $e->selfGet('itemForm')->inPanel(static::ITEM_FORM_PANEL_ID) && $e->selfGet('getStyleFormComponent')->inPanel('item_styles_form') && $e->selfGet('itemStylesForm')->inPanel(static::ITEM_FORM_STYLES_ID) && $e->selfGet('getCopyBlockPanel')->inPanel(static::COPY_BLOCK_PANEL_ID))->col($this->model->id ? 'col-md-8' : 'col-md-12'),
                        $this->model->id ? _DeleteButton('cms::cms.clear')->byKey($this->model)->refresh('page_design_form')->class('align-right')->col('col-md-4') : null,
                    )->class('items-center'),
                    _Input('cms::cms.zone-name')->name('name_pi'),
                    !$this->model->id ? _Panel(
                        _Html(''),
                    )->id(static::COPY_BLOCK_PANEL_ID)->class('mt-4') : null,
                    _Panel(
                        $this->model->block_type ? $this->model->getPageItemType()?->blockTypeEditorElement() : _Html(''),
                    )->id(static::ITEM_FORM_PANEL_ID)->class('mt-4'),
                    _FlexBetween(
                        _SubmitButton('cms::cms.save-zone-and-new')->class('ml-auto mt-3')
                            ->onSuccess(fn($e) => $e->selfGet('refreshItemForm')->inPanel(PageDesignForm::PAGE_ITEM_PANEL) && $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                        _SubmitButton('cms::cms.save-zone')->class('ml-auto mt-3')
                            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                    )->class('gap-4'),
                )
            )->label('cms::cms.zone-content'),
            _Tab(
                _Rows(
                    _Panel(
                        $this->getStyleFormComponent(),
                    )->id('item_styles_form'),
                    _FlexBetween(
                        _Button('cms::cms.set-generic-styles-to-block')->selfPost('setGenericStyles')->withAllFormValues(),
                        _SubmitButton('cms::cms.save')->class('ml-auto')
                            ->onSuccess(fn($e) => $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL)),
                    )->class('gap-4 mt-3'),
                )->class('!mb-2')
            )->label('cms::cms.zone-styles'),
        );
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
                'panel_id' => PageDesignForm::PAGE_ITEM_PANEL,
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
            ->onSuccess(fn($e) => $e->selfGet('refreshItemForm')->inPanel(PageDesignForm::PAGE_ITEM_PANEL) && $e->selfGet('getPagePreview')->inPanel(PageDesignForm::PREVIEW_PAGE_PANEL))
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
