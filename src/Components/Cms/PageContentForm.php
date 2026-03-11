<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageContentForm extends Form
{
    public $id = 'page_content_form';
    protected $routeName;

    protected $withDesign = true;
    protected $prefixGroup = "";

    protected $useEmailEditor = true;

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        if ($this->useEmailEditor && $this->model?->id) {
            return $this->emailEditorRender();
        }

        return $this->legacyRender();
    }

    protected function emailEditorRender()
    {
        return PageEditor::getEmailEditorComponent($this->prefixGroup, $this->model?->id);
    }

    protected function legacyRender()
    {
        return _Rows(
            $this->top(),
            _Tabs(
                _Tab(
                    PageEditor::getPageInfoFormComponent($this->prefixGroup, $this->model?->id),
                )->label('cms::cms.page-info'),
                !$this->model?->id ? null : _Tab(
                    PageEditor::getPageStyleFormComponent($this->prefixGroup, $this->model?->id),
                )->label('cms::cms.page-styles'),
            ),
            !$this->withDesign ? null : PageEditor::getPageDesignFormComponent($this->prefixGroup, $this->model?->id),
        );
    }

    protected function top()
    {
        return _Rows();
    }

    public function duplicatePage()
    {
        $page = PageModel::findOrFail(request('id'));

        $newPage = $page->createPageCopyWithRelations();

        return $newPage;
    }
}
