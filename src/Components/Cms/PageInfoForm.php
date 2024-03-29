<?php
namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Illuminate\Support\Facades\Route;
use Kompo\Form;

class PageInfoForm extends Form
{
    public function created(){
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function response()
    {
        return redirect()->route(request('route'), ['id' => $this->model->id]);
    }

    public function render()
    {
        return _Card(
            _Hidden()->name('route', false)->value(Route::currentRouteName()),
            _Rows($this->inputs()),
            $this->extraInputs(),
            $this->submitMethod(),
        );
    }

    protected function extraInputs()
    {
        return _Rows();
    }

    protected function inputs()
    {
        return [
            _Translatable('cms::cms.title')->name('title')->class('mb-2 whiteField'),
        ];
    }

    protected function submitMethod()
    {
        return _SubmitButton('cms::cms.save')->class('mt-4');
    }
}