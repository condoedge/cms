<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Features\Teams;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Form;

class TemplateGallery extends Form
{
    public $id = 'template-gallery';

    protected $prefixGroup = "";

    public function render()
    {
        $templates = $this->getTemplates();

        return _Rows(
            _FlexBetween(
                _Html('cms::cms.template-gallery')->class('text-base font-semibold text-gray-800'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { document.querySelector(".vlTemplateModal").remove() }'),
            )->class('mb-4'),

            _Html('cms::cms.template-gallery-desc')->class('text-sm text-gray-500 mb-5'),

            $templates->isEmpty()
                ? $this->emptyState()
                : $this->templateGrid($templates),

            _Html($this->galleryStyles()),
        )->class('vlTemplateGalleryInner');
    }

    protected function getTemplates()
    {
        $query = PageModel::where('is_template', true);

        if (Features::hasFeature('teams')) {
            $query->where(function ($q) {
                $q->where('team_id', auth()->user()->current_team_id)
                  ->orWhereNull('team_id');
            });
        }

        return $query->orderByDesc('updated_at')->get();
    }

    protected function emptyState()
    {
        return _Rows(
            _Html()->icon(_Sax('document-copy', 40))->class('text-gray-300 mb-3'),
            _Html('cms::cms.no-templates-yet')->class('text-sm text-gray-400 text-center'),
            _Html('cms::cms.no-templates-desc')->class('text-xs text-gray-300 text-center mt-1'),
        )->class('vlTemplateEmpty');
    }

    protected function templateGrid($templates)
    {
        return _Rows(
            ...$templates->map(fn($template) => $this->templateCard($template)),
        )->class('vlTemplateGrid');
    }

    protected function templateCard($template)
    {
        $blockCount = $template->orderedMainPageItems()->count();

        return _FlexBetween(
            _Flex(
                _Rows(
                    _Html()->icon(_Sax('document-text', 24))->class('text-gray-400'),
                )->class('vlTemplateCardIcon'),
                _Rows(
                    _Html($template->title ?: __('cms::cms.untitled-email'))->class('text-sm font-medium text-gray-800'),
                    _Html(trans_choice('cms::cms.template-block-count', $blockCount, ['count' => $blockCount]))
                        ->class('text-xs text-gray-400 mt-0.5'),
                ),
            )->class('items-center gap-3 min-w-0 flex-1'),
            _Flex(
                _Button('cms::cms.use-template')
                    ->class('vlTemplateUseBtn')
                    ->selfPost('createFromTemplate', ['template_id' => $template->id])
                    ->onSuccess(fn($e) => $e->run('() => { document.querySelector(".vlTemplateModal").remove(); window.location.reload(); }')),
                _Link()->icon(_Sax('trash', 16))
                    ->class('vlTemplateDeleteBtn')
                    ->balloon('cms::cms.delete-template', 'down')
                    ->selfPost('deleteTemplate', ['template_id' => $template->id])
                    ->onSuccess(fn($e) => $e->selfGet('refreshGallery')->inPanel('template-gallery-panel')),
            )->class('items-center gap-2 flex-shrink-0'),
        )->class('vlTemplateCard');
    }

    public function createFromTemplate()
    {
        $template = PageModel::findOrFail(request('template_id'));
        $pageId = request('target_page_id');

        if (!$pageId) return;

        $targetPage = PageModel::findOrFail($pageId);

        // Clear existing items on the target page
        $targetPage->pageItems()->get()->each->delete();

        // Copy all items from template
        $template->orderedMainPageItems()->get()->each(function ($item) use ($targetPage) {
            $newItem = $item->replicate();
            $newItem->page_id = $targetPage->id;
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

        // Copy page styles
        if ($template->styles) {
            $existingStyles = $targetPage->styles;
            if ($existingStyles) {
                $existingStyles->content = $template->styles->content;
                $existingStyles->save();
            } else {
                $newStyles = $template->styles->replicate();
                $newStyles->page_id = $targetPage->id;
                $newStyles->save();
            }
        }
    }

    public function deleteTemplate()
    {
        $template = PageModel::findOrFail(request('template_id'));

        if ($template->is_template) {
            $template->forceDelete();
        }
    }

    public function refreshGallery()
    {
        return new static(null, []);
    }

    protected function galleryStyles()
    {
        return '<style>
            .vlTemplateModal {
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
            .vlTemplateGalleryInner {
                background: #ffffff;
                border-radius: 12px;
                padding: 24px;
                width: 560px;
                max-width: 90vw;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            }
            .vlTemplateEmpty {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px 20px;
            }
            .vlTemplateGrid {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .vlTemplateCard {
                padding: 12px 16px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                transition: all 0.15s;
            }
            .vlTemplateCard:hover {
                border-color: #93c5fd;
                background: #fafbff;
            }
            .vlTemplateCardIcon {
                width: 40px;
                height: 40px;
                min-width: 40px;
                background: #f3f4f6;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .vlTemplateUseBtn {
                padding: 6px 14px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 6px !important;
                border: none !important;
                white-space: nowrap;
            }
            .vlTemplateUseBtn:hover {
                background: #1d4ed8 !important;
            }
            .vlTemplateDeleteBtn {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                color: #9ca3af;
                transition: all 0.15s;
            }
            .vlTemplateDeleteBtn:hover {
                background: #fef2f2;
                color: #dc2626;
            }
        </style>';
    }
}
