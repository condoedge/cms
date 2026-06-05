<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageEditorLayout extends Form
{
    public $id = 'page-editor-layout';
    protected $prefixGroup = "";

    public const PREVIEW_PANEL = 'page-editor-preview-panel';
    public const PROPERTY_PANEL = 'page-editor-property-panel';
    public const BLOCK_LIBRARY_PANEL = 'page-editor-block-library-panel';

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        if (!$this->model->id) {
            return $this->emptyState();
        }

        $this->bindEditorJs();

        return _Rows(
            _Link('cms::cms.skip-to-canvas')->href('#'.static::PREVIEW_PANEL)->class('vlSkipLink'),
            $this->topBar(),
            $this->editorBody(),
        )->class('vlPageEditorWrapper')->attr(['role' => 'application', 'aria-label' => __('cms::cms.page-editor')]);
    }

    protected function emptyState()
    {
        return _Rows(
            _Html('cms::cms.first-save-page')->class('text-xl text-gray-500 text-center p-12'),
        );
    }

    protected function topBar()
    {
        return new EditorTopBar($this->model->id, [
            'prefix_group' => $this->prefixGroup,
        ]);
    }

    protected function editorBody()
    {
        return _Div(
            $this->leftPanel(),
            $this->centerPanel(),
        )->class('vlEditorBody');
    }

    protected function leftPanel()
    {
        return _Div(
            _Panel(
                new BlockLibraryPanel(null, [
                    'page_id' => $this->model->id,
                    'prefix_group' => $this->prefixGroup,
                ]),
            )->id(static::BLOCK_LIBRARY_PANEL),
        )->class('vlEditorLeftPanel')->attr(['role' => 'complementary', 'aria-label' => __('cms::cms.blocks')]);
    }

    protected function centerPanel()
    {
        $bgColor = $this->model->getExteriorBackgroundColor();
        $contentBg = $this->model->getContentBackgroundColor();
        $maxWidth = $this->model->getContentMaxWidth();

        return _Div(
            _Div(
                _Panel(
                    PageEditor::getPagePreviewComponent($this->prefixGroup, [
                        'page_id' => $this->model->id,
                        'panel_id' => static::PROPERTY_PANEL,
                        'with_editor' => true,
                    ]),
                )->id(static::PREVIEW_PANEL),
            )->class('vlCanvasFrame')->style('max-width:'.(int) $maxWidth.'px;background:'.$contentBg.';'),
        )->class('vlEditorCenterPanel overflow-y-auto')->style('background:'.$bgColor.'; max-height: calc(100vh - 64px);')
            ->attr(['role' => 'main', 'aria-label' => __('cms::cms.canvas')]);
    }

    protected function bindEditorJs(): void
    {
        $js = file_get_contents(__DIR__.'/../../../resources/js/page-editor.js');

        $this->onLoad(fn ($e) => $e->run('() => { ('.$js.')(); }'));
    }
}
