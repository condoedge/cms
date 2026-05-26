<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class EmailEditorLayout extends Form
{
    public $id = 'email-editor-layout';
    protected $prefixGroup = "";

    public const PREVIEW_PANEL = 'email-editor-preview-panel';
    public const PROPERTY_PANEL = 'email-editor-property-panel';
    public const BLOCK_LIBRARY_PANEL = 'email-editor-block-library-panel';

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
            $this->emptyBlockPseudoStyle(),
            $this->topBar(),
            $this->editorBody(),
        )->class('vlEmailEditorWrapper')->attr(['role' => 'application', 'aria-label' => __('cms::cms.email-editor')]);
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
            $this->drawerBackdrop(),
            $this->rightPanel(),
        )->class('vlEditorBody');
    }

    protected function drawerBackdrop()
    {
        return _Link()->class('vlDrawerBackdrop')
            ->run('() => { if (window.vlEmailEditor) vlEmailEditor.closeDrawer() }');
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
        )->class('vlEditorCenterPanel')->style('background:'.$bgColor.';')
            ->attr(['role' => 'main', 'aria-label' => __('cms::cms.canvas')]);
    }

    protected function rightPanel()
    {
        return _Div(
            _Flex(
                _Html('cms::cms.block-properties')->class('vlDrawerTitle'),
                _Link()->icon('x')->class('vlDrawerClose')
                    ->run('() => { vlEmailEditor.closeDrawer() }'),
            )->class('vlDrawerHeader'),
            _Panel()->id(static::PROPERTY_PANEL),
        )->class('vlEditorRightPanel')->attr(['role' => 'complementary', 'aria-label' => __('cms::cms.block-properties')]);
    }

    // Pseudo-element content needs translation interpolation; can't be inlined onto an element.
    // Kept narrow: only the ::after rule.
    protected function emptyBlockPseudoStyle()
    {
        $label = e(__('cms::cms.click-to-edit'));

        return _Html('<style>.vlEmailBlock.vlEmailBlockEmpty::after{content:attr(data-block-type) " — '.$label.'";}</style>');
    }

    protected function bindEditorJs(): void
    {
        $js = file_get_contents(__DIR__.'/../../../resources/js/email-editor.js');
        $panelId = json_encode(static::PROPERTY_PANEL);

        $this->onLoad(fn ($e) => $e->run('() => { ('.$js.')('.$panelId.'); }'));
    }
}
