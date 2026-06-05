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

            // Super-admins can publish the template GLOBALLY (available to every
            // team), not just to their own team. Hiding the toggle is NOT the
            // authorization — saveAsTemplate() re-checks super-admin server-side.
            $this->canMakeGlobal()
                ? _Rows(
                    _Checkbox('cms::cms.make-global-template')->name('make_global', false),
                    _Html('cms::cms.make-global-template-desc')->class('text-xs text-gray-400 mt-1'),
                )->class('mt-4 pt-4 border-t border-gray-100')
                : null,
        ];
    }

    public function saveAsTemplate()
    {
        $name = request('template_name');
        if (!$name || !$this->model->id) return;

        // Authorization gate: only a super-admin can create a GLOBAL template,
        // even if the make_global flag is forged into the request.
        $global = $this->canMakeGlobal() && (bool) request('make_global');

        app(PageTemplateService::class)->createTemplateFromPage($this->model, $name, $global);
    }

    protected function canMakeGlobal(): bool
    {
        return function_exists('isSuperAdmin') && isSuperAdmin();
    }
}
