<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Form;

class SaveAsTemplateForm extends Form
{
    public $id = 'save-as-template-form';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::find($this->prop('page_id')) ?? PageModel::make());
    }

    public function render()
    {
        return _Rows(
            _FlexBetween(
                _Html('cms::cms.save-as-template')->class('text-base font-semibold text-gray-800'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { document.querySelector(".vlSaveTemplateModal").remove() }'),
            )->class('mb-4'),

            _Html('cms::cms.save-as-template-desc')->class('text-sm text-gray-500 mb-4'),

            _Input('cms::cms.template-name')
                ->name('template_name')
                ->value($this->model->title ? $this->model->title . ' - Template' : '')
                ->class('mb-4'),

            _FlexEnd(
                _Link('cms::cms.cancel')
                    ->class('vlSaveTemplateCancelBtn')
                    ->run('() => { document.querySelector(".vlSaveTemplateModal").remove() }'),
                _Button('cms::cms.save-as-template')
                    ->class('vlSaveTemplateSaveBtn')
                    ->selfPost('saveAsTemplate')
                    ->withAllFormValues()
                    ->onSuccess(fn($e) => $e->run('() => { document.querySelector(".vlSaveTemplateModal").remove(); vlEmailEditor.showToast("'.__('cms::cms.template-saved').'") }')),
            )->class('gap-3'),

            _Html($this->modalStyles()),
        )->class('vlSaveTemplateFormInner');
    }

    public function saveAsTemplate()
    {
        $name = request('template_name');
        if (!$name) return;

        $source = $this->model;
        if (!$source->id) return;

        // Create the template page
        $template = $source->replicate();
        $template->title = $name;
        $template->is_template = true;
        $template->published_at = null;
        $template->sent_at = null;
        $template->page_id = null;

        if (Features::hasFeature('teams')) {
            $template->team_id = auth()->user()->current_team_id;
        }

        $template->save();

        // Copy page styles
        if ($source->styles) {
            $newStyles = $source->styles->replicate();
            $newStyles->page_id = $template->id;
            $newStyles->save();
        }

        // Copy all page items
        $source->orderedMainPageItems()->get()->each(function ($item) use ($template) {
            $newItem = $item->replicate();
            $newItem->page_id = $template->id;
            $newItem->save(['skip_validation' => true]);

            if ($item->styles) {
                $newStyles = $item->styles->replicate();
                $newItem->styles()->save($newStyles);
            }

            $item->groupPageItems()->each(function ($groupItem) use ($newItem) {
                $newGroupItem = $groupItem->replicate();
                $newGroupItem->group_page_item_id = $newItem->id;
                $newGroupItem->page_id = $newItem->page_id;
                $newGroupItem->save(['skip_validation' => true]);

                if ($groupItem->styles) {
                    $newGroupStyles = $groupItem->styles->replicate();
                    $newGroupItem->styles()->save($newGroupStyles);
                }
            });
        });
    }

    protected function modalStyles()
    {
        return '<style>
            .vlSaveTemplateModal {
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
            .vlSaveTemplateFormInner {
                background: #ffffff;
                border-radius: 12px;
                padding: 24px;
                width: 440px;
                max-width: 90vw;
                box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            }
            .vlSaveTemplateCancelBtn {
                padding: 7px 16px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                transition: all 0.15s;
            }
            .vlSaveTemplateCancelBtn:hover {
                background: #f9fafb;
            }
            .vlSaveTemplateSaveBtn {
                padding: 7px 20px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                border: none !important;
            }
            .vlSaveTemplateSaveBtn:hover {
                background: #1d4ed8 !important;
            }
        </style>';
    }
}
