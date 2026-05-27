<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageDesignForm extends Form
{
    public $id = 'page_design_form';
    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        return PageEditor::getPageEditorComponent($this->prefixGroup, $this->model?->id);
    }
}
