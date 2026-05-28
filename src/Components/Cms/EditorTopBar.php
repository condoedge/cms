F<?php

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

    protected function titleInput()
    {
        if (!$this->model->id) return null;

        return _Input()
            ->name('title_display', false)
            ->value($this->model->title ?: __('cms::cms.untitled-page'))
            ->placeholder('cms::cms.untitled-page')
            ->class('vlEditorTopBarTitle !mb-0')
            ->attr(['aria-label' => __('cms::cms.page-title')]);
    }

    protected function backButton()
    {
        return _Link()->icon('arrow-left')
            ->class('vlEditorTopBarBack')
            ->attr(['aria-label' => __('cms::cms.back')])
            ->run('() => { window.history.back() }');
    }

    protected function centerSection()
    {
        return _Flex(
            $this->deviceToggles(),
        )->class('items-center gap-3');
    }

    protected function deviceToggles()
    {
        return _Flex(
            _Link()->icon(_Sax('monitor',20))
                ->balloon('cms::cms.preview-desktop', 'down')
                ->class('vlDeviceToggle vlDeviceToggleActive')
                ->attr(['data-device' => 'desktop'])
                ->run('() => { vlPageEditor.setDevice("desktop") }'),
            _Link()->icon(_Sax('mobile',20))
                ->balloon('cms::cms.preview-mobile', 'down')
                ->class('vlDeviceToggle')
                ->attr(['data-device' => 'mobile'])
                ->run('() => { vlPageEditor.setDevice("mobile") }'),
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
                ->run('() => { vlPageEditor.toggleMobilePanel("blocks") }'),
            _Link()->icon(_Sax('setting-2', 18))
                ->class('vlMobilePanelToggle vlEditorActionBtn')
                ->attr(['aria-label' => __('cms::cms.toggle-properties-panel')])
                ->run('() => { vlPageEditor.toggleMobilePanel("properties") }'),
        )->class('items-center gap-1');
    }

    protected function moreActionsDropdown()
    {
        if (!$this->model->id) return null;

        return _Dropdown()->icon(_Sax('more', 20))
            ->button('vlEditorActionBtn font-normal')
            ->openOnClick()
            ->alignRight()
            ->noCaret()
            ->submenu(
                _DropdownLink('cms::cms.send-test')->icon(_Sax('sms', 16))
                    ->class('pt-4')
                    ->selfGet('getSendTestModal')->inModal(),
                _DropdownLink('cms::cms.preview-with-data')->icon(_Sax('eye', 16))
                    ->selfGet('getPreviewVarsModal')->inModal(),
                _DropdownLink('cms::cms.export-html')->icon(_Sax('document-download', 16))
                    ->href('page-editor.export-html', ['page_id' => $this->model->id])->inNewTab(),
                _DropdownLink('cms::cms.save-as-template')->icon(_Sax('document-favorite', 16))
                    ->selfGet('getSaveTemplateModal')->inModal(),
                _DropdownLink('cms::cms.browse-templates')->icon(_Sax('element-4', 16))
                    ->class('pb-4')
                    ->selfGet('getTemplateGalleryModal')->inModal(),
            );
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
            ->alert('cms::cms.saved-successfully');
    }

    public function savePage()
    {
        if (!$this->model->id) return;

        $title = request('title_display');
        if ($title && $title !== __('cms::cms.untitled-page')) {
            $this->model->title = $title;
            $this->model->save();
        }
    }

    public function getSendTestModal()
    {
        return new SendTestEmailForm($this->model->id);
    }

    public function getSaveTemplateModal()
    {
        return new SaveAsTemplateForm($this->model->id);
    }

    public function getPreviewVarsModal()
    {
        return new PreviewWithVariablesForm($this->model->id);
    }

    public function getTemplateGalleryModal()
    {
        return new TemplateGallery($this->model->id);
    }
}
 
