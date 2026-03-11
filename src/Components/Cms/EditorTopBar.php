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
            $this->deviceToggles(),
        )->class('items-center gap-2');
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
            $this->previewButton(),
            $this->saveButton(),
        )->class('items-center gap-2 flex-shrink-0');
    }

    protected function previewButton()
    {
        if (!$this->model->id) return null;

        return _Link('cms::cms.preview')
            ->icon(_Sax('eye',20))
            ->class('vlEditorPreviewBtn')
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
}
