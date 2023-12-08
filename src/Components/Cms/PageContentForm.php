<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\PageEditor;
use Kompo\Form;

class PageContentForm extends Form
{
    public $id = 'page_content_form';

    public function created(){
        $this->model(app('page-model'));
    }

    public function render()
    {
        return _Rows(
            _Card(
                _Input('campaign.title')->name('title')->class('mb-2'),
                $this->extraInputs(),
                _SubmitButton('campaign.save')->class('mt-4')->refresh(),
            )->class('p-4'),
            PageEditor::getPageDesignFormComponent($this->model?->id),
        );
    }

    public function extraInputs()
    {
        return _Rows();
    }
}