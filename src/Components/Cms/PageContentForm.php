<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Illuminate\Support\Facades\Route;
use Kompo\Form;

class PageContentForm extends Form
{
    public $id = 'page_content_form';
    protected $routeName;

    protected $withDesign = true;
    protected $prefixGroup = "";

    public function created(){
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function render()
    {
        return _Rows(
            PageEditor::getPageInfoFormComponent($this->prefixGroup, $this->model?->id),
            !$this->withDesign ? null : PageEditor::getPageDesignFormComponent($this->prefixGroup, $this->model?->id),
        );
    }
}
