<?php
namespace Anonimatrix\PageEditor\Components\Cms;

use Illuminate\Support\Facades\Route;
use Kompo\Form;

class PageInfoForm extends Form 
{
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
            _Translatable('translate.page-editor.title')->name('title')->class('mb-2'),
        ];
    }

    protected function submitMethod()
    {
        return _SubmitButton('translate.page-editor.save')->class('mt-4');
    }
}