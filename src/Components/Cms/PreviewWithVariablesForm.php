<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

class PreviewWithVariablesForm extends Modal
{
    public $id = 'preview-with-variables-form';

    public $_Title = 'cms::cms.preview-with-data';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::findOrFail($this->modelKey()));
    }

    public function headerButtons()
    {
        return _SubmitButton('cms::cms.generate-preview')
            ->selfPost('generatePreview')
            ->withAllFormValues()
            ->inNewTab();
    }

    public function body()
    {
        return [
            _Html('cms::cms.preview-with-data-desc')->class('text-sm text-gray-500 mb-4'),

            $this->variableInputs(),
        ];
    }

    protected function variableInputs()
    {
        $variables = $this->getAvailableVariables();

        if ($variables->isEmpty()) {
            return _Html('cms::cms.no-variables-available')->class('text-sm text-gray-400 italic py-4');
        }

        return _Rows(
            ...$variables->map(fn($label, $key) =>
                _Input($label)
                    ->name('var_' . $key, false)
                    ->value($this->getSampleValue($key))
                    ->class('mb-3')
            ),
        );
    }

    protected function getAvailableVariables()
    {
        $vars = collect([
            'contact_name' => __('cms::cms.var-contact-name'),
            'contact_email' => __('cms::cms.var-contact-email'),
        ]);

        if (Features::hasFeature('teams')) {
            $vars = $vars->merge([
                'team_name' => __('cms::cms.var-team-name'),
            ]);
        }

        // Allow extending via config
        $customVars = config('page-editor.preview_variables', []);
        foreach ($customVars as $key => $label) {
            $vars[$key] = $label;
        }

        return $vars;
    }

    protected function getSampleValue($key)
    {
        $samples = [
            'contact_name' => 'Jean Dupont',
            'contact_email' => 'jean.dupont@example.com',
            'team_name' => 'Mon Organisation',
        ];

        return $samples[$key] ?? '';
    }

    public function generatePreview()
    {
        $page = $this->model;
        if (!$page->id) return;

        $variables = [];
        foreach (request()->all() as $key => $value) {
            if (str_starts_with($key, 'var_')) {
                $varKey = substr($key, 4);
                $variables[$varKey] = $value;
            }
        }

        $htmlContent = $page->getHtmlContent($variables);

        $bgColor = $page->getExteriorBackgroundColor();
        $contentBg = $page->getContentBackgroundColor();
        $textColor = $page->getTextColor();
        $linkColor = $page->getLinkColor();
        $fontSize = $page->getFontSize();
        $maxWidth = $page->getContentMaxWidth();
        $fontFamily = $page->getFontFamily();

        $sendTestForm = new SendTestEmailForm(null, ['page_id' => $page->id]);
        $fullHtml = $sendTestForm->buildEmailHtml($htmlContent, $bgColor, $contentBg, $textColor, $linkColor, $fontSize, $maxWidth, $fontFamily);

        return response($fullHtml)->header('Content-Type', 'text/html');
    }
}
