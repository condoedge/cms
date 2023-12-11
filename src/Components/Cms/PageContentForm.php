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

    public function created(){
        $this->model(PageModel::find($this->prop('id')) ?? PageModel::make());
    }

    public function response()
    {
        return redirect()->route(request('route'), ['id' => $this->model->id]);
    }

    public function render()
    {
        return _Rows(
            _Card(
                _Hidden()->name('route', false)->value(Route::currentRouteName()),
                _Rows($this->inputs()),
                $this->extraInputs(),
                $this->submitMethod(),
            ),
            _Html()->class('border-t-2 border-gray-400 mt-4 pt-4'),
            !$this->withDesign ? null : PageEditor::getPageDesignFormComponent($this->model?->id),
        );
    }

    protected function extraInputs()
    {
        return _Rows();
    }

    protected function inputs()
    {
        return [
            _Translatable('campaign.title')->name('title')->class('mb-2'),
        ];
    }

    protected function submitMethod()
    {
        return _SubmitButton('campaign.save')->class('mt-4');
    }
}
