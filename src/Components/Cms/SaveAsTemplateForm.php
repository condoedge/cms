<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageTemplateService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

class SaveAsTemplateForm extends Modal
{
    public $id = 'save-as-template-form';

    public $_Title = 'cms::cms.save-as-template';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::findOrFail($this->modelKey()));
    }

    public function headerButtons()
    {
        return _SubmitButton('cms::cms.save-as-template')
            ->selfPost('saveAsTemplate')
            ->withAllFormValues()
            ->alert('cms::cms.template-saved')
            ->closeModal();
    }

    public function body()
    {
        return [
            _Html('cms::cms.save-as-template-desc')->class('text-sm text-gray-500 mb-4'),

            _Input('cms::cms.template-name')
                ->name('template_name', false)
                ->value($this->model->title ? $this->model->title . ' - Template' : ''),
        ];
    }

    public function saveAsTemplate()
    {
        $name = request('template_name');
        if (!$name || !$this->model->id) return;

        app(PageTemplateService::class)->createTemplateFromPage($this->model, $name);
    }
}
