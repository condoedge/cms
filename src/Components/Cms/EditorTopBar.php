<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Form;

class EditorTopBar extends Form
{
    public $id = 'editor-top-bar';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        return _FlexBetween(
            $this->leftSection(),
            $this->centerSection(),
            $this->rightSection(),
        )->class('vlEditorTopBar');
    }

    protected function leftSection()
    {
        return _Flex(
            $this->backButton(),
            $this->titleInput(),
        )->class('items-center gap-3 flex-1 min-w-0');
    }

    protected function backButton()
    {
        return _Link()->icon('arrow-left')
            ->class('vlEditorTopBarBack');
    }

    protected function titleInput()
    {
        return _Input()->name('title_display', false)
            ->value($this->model->title ?: __('cms::cms.untitled-email'))
            ->class('vlEditorTopBarTitle');
    }

    protected function centerSection()
    {
        return _Flex(
            $this->undoRedoButtons(),
            $this->deviceToggles(),
        )->class('items-center gap-3');
    }

    protected function undoRedoButtons()
    {
        if (!$this->model->id) return null;

        return _Flex(
            _Link()->icon(_Sax('undo', 18))
                ->class('vlUndoRedoBtn vlUndoRedoBtnDisabled')
                ->attr(['data-undo-btn' => true, 'title' => __('cms::cms.nothing-to-undo'), 'aria-label' => __('cms::cms.undo')])
                ->run('() => { vlEmailEditor.undo() }'),
            _Link()->icon(_Sax('redo', 18))
                ->class('vlUndoRedoBtn vlUndoRedoBtnDisabled')
                ->attr(['data-redo-btn' => true, 'title' => __('cms::cms.nothing-to-redo'), 'aria-label' => __('cms::cms.redo')])
                ->run('() => { vlEmailEditor.redo() }'),
        )->class('vlUndoRedoGroup');
    }

    protected function deviceToggles()
    {
        return _Flex(
            _Link()->icon(_Sax('monitor',20))
                ->balloon('cms::cms.preview-desktop', 'down')
                ->class('vlDeviceToggle vlDeviceToggleActive')
                ->attr(['data-device' => 'desktop'])
                ->run('() => { vlEmailEditor.setDevice("desktop") }'),
            _Link()->icon(_Sax('mobile',20))
                ->balloon('cms::cms.preview-mobile', 'down')
                ->class('vlDeviceToggle')
                ->attr(['data-device' => 'mobile'])
                ->run('() => { vlEmailEditor.setDevice("mobile") }'),
        )->class('vlDeviceToggleGroup');
    }

    protected function rightSection()
    {
        return _Flex(
            $this->mobilePanelToggles(),
            $this->moreActionsDropdown(),
            $this->previewButton(),
            $this->saveButton(),
        )->class('items-center gap-2 flex-shrink-0');
    }

    protected function mobilePanelToggles()
    {
        if (!$this->model->id) return null;

        return _Flex(
            _Link()->icon(_Sax('element-3', 18))
                ->class('vlMobilePanelToggle vlEditorActionBtn')
                ->attr(['aria-label' => __('cms::cms.toggle-blocks-panel')])
                ->run('() => { vlEmailEditor.toggleMobilePanel("blocks") }'),
            _Link()->icon(_Sax('setting-2', 18))
                ->class('vlMobilePanelToggle vlEditorActionBtn')
                ->attr(['aria-label' => __('cms::cms.toggle-properties-panel')])
                ->run('() => { vlEmailEditor.toggleMobilePanel("properties") }'),
        )->class('items-center gap-1');
    }

    protected function moreActionsDropdown()
    {
        if (!$this->model->id) return null;

        return _Div(
            _Link()->icon(_Sax('more', 20))
                ->class('vlEditorActionBtn')
                ->attr(['data-actions-trigger' => true])
                ->run('(el) => {
                    var menu = el.closest(".vlActionsMenuWrap").querySelector(".vlActionsMenu");
                    if (menu) { menu.remove(); return; }
                }')
                ->selfGet('getActionsMenu')->inPanel('actions-menu-panel'),
            _Panel()->id('actions-menu-panel'),
        )->class('vlActionsMenuWrap')->style('position: relative;');
    }

    protected function previewButton()
    {
        if (!$this->model->id) return null;

        return _Link('cms::cms.preview')
            ->icon(_Sax('eye',18))
            ->class('vlEditorActionBtn')
            ->href('page.preview', ['page_id' => $this->model->id])
            ->inNewTab();
    }

    protected function saveButton()
    {
        return _Button('cms::cms.save')
            ->class('vlEditorSaveBtn')
            ->selfPost('savePage')
            ->withAllFormValues()
            ->onSuccess(fn($e) => $e->run('() => { vlEmailEditor.showToast("'.__('cms::cms.saved-successfully').'") }'));
    }

    public function savePage()
    {
        if (!$this->model->id) return;

        $title = request('title_display');
        if ($title && $title !== __('cms::cms.untitled-email')) {
            $this->model->title = $title;
            $this->model->save();
        }
    }

    public function getSendTestModal()
    {
        return _Div(
            new SendTestEmailForm(null, [
                'page_id' => $this->model->id,
            ]),
        )->class('vlTestEmailModal');
    }

    public function getSaveTemplateModal()
    {
        return _Div(
            new SaveAsTemplateForm(null, [
                'page_id' => $this->model->id,
            ]),
        )->class('vlSaveTemplateModal');
    }

    public function getTemplateGalleryModal()
    {
        return _Div(
            _Panel(
                new TemplateGallery(null, [
                    'target_page_id' => $this->model->id,
                ]),
            )->id('template-gallery-panel'),
        )->class('vlTemplateModal');
    }

    public function getActionsMenu()
    {
        return _Rows(
            _Link('cms::cms.send-test')->icon(_Sax('sms', 16))
                ->class('vlActionsMenuItem')
                ->selfGet('getSendTestModal')->inModal(),
            _Link('cms::cms.preview-with-data')->icon(_Sax('eye', 16))
                ->class('vlActionsMenuItem')
                ->selfGet('getPreviewVarsModal')->inModal(),
            _Link('cms::cms.export-html')->icon(_Sax('document-download', 16))
                ->class('vlActionsMenuItem')
                ->href('page-editor.export-html', ['page_id' => $this->model->id])->inNewTab(),
            _Div()->class('vlActionsMenuDivider'),
            _Link('cms::cms.save-as-template')->icon(_Sax('document-favorite', 16))
                ->class('vlActionsMenuItem')
                ->selfGet('getSaveTemplateModal')->inModal(),
            _Link('cms::cms.browse-templates')->icon(_Sax('element-4', 16))
                ->class('vlActionsMenuItem')
                ->selfGet('getTemplateGalleryModal')->inModal(),
        )->class('vlActionsMenu');
    }

    public function getPreviewVarsModal()
    {
        return _Div(
            new PreviewWithVariablesForm(null, [
                'page_id' => $this->model->id,
            ]),
        )->class('vlPreviewVarsModal');
    }
}
