<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Features\Teams;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Form;

class PreviewWithVariablesForm extends Form
{
    public $id = 'preview-with-variables-form';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::find($this->prop('page_id')) ?? PageModel::make());
    }

    public function render()
    {
        return _Rows(
            _FlexBetween(
                _Html('cms::cms.preview-with-data')->class('text-base font-semibold text-gray-800'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { document.querySelector(".vlPreviewVarsModal").remove() }'),
            )->class('mb-4'),

            _Html('cms::cms.preview-with-data-desc')->class('text-sm text-gray-500 mb-4'),

            $this->variableInputs(),

            _FlexEnd(
                _Link('cms::cms.cancel')
                    ->class('vlPreviewVarsCancelBtn')
                    ->run('() => { document.querySelector(".vlPreviewVarsModal").remove() }'),
                _Button('cms::cms.generate-preview')
                    ->class('vlPreviewVarsGenerateBtn')
                    ->selfPost('generatePreview')
                    ->withAllFormValues()
                    ->inNewTab(),
            )->class('gap-3 mt-4'),

            _Html($this->modalStyles()),
        )->class('vlPreviewVarsFormInner');
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
                    ->class('mb-3 vlCompactInput')
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

    protected function modalStyles()
    {
        return '<style>
            .vlPreviewVarsModal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: vlModalFadeIn 0.15s ease;
            }
            .vlPreviewVarsFormInner {
                background: #ffffff;
                border-radius: 12px;
                padding: 24px;
                width: 440px;
                max-width: 90vw;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            }
            .vlPreviewVarsCancelBtn {
                padding: 7px 16px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                border: 1px solid #d1d5db;
                border-radius: 8px;
            }
            .vlPreviewVarsCancelBtn:hover {
                background: #f9fafb;
            }
            .vlPreviewVarsGenerateBtn {
                padding: 7px 20px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                border: none !important;
            }
            .vlPreviewVarsGenerateBtn:hover {
                background: #1d4ed8 !important;
            }
        </style>';
    }
}
