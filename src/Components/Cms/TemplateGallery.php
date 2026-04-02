<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

class TemplateGallery extends Modal
{
    public $id = 'template-gallery';

    public $_Title = 'cms::cms.template-gallery';

    protected $prefixGroup = "";
    protected $noHeaderButtons = true;
    protected $targetPageId;

    public function created()
    {
        $this->targetPageId = $this->modelKey();
    }

    public function body()
    {
        $templates = $this->getTemplates();

        return [
            _Html('cms::cms.template-gallery-desc')->class('text-sm text-gray-500 mb-5'),

            $templates->isEmpty()
                ? $this->emptyState()
                : $this->templateGrid($templates),
        ];
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
        )->class('flex flex-col items-center py-10');
    }

    protected function templateGrid($templates)
    {
        return _Rows(
            ...$templates->map(fn($template) => $this->templateCard($template)),
        )->class('flex flex-col gap-2');
    }

    protected function templateCard($template)
    {
        $blockCount = $template->orderedMainPageItems()->count();

        return _FlexBetween(
            _Flex(
                _Rows(
                    _Html()->icon(_Sax('document-text', 24))->class('text-gray-400'),
                )->class('w-10 h-10 min-w-[40px] bg-gray-100 rounded-lg flex items-center justify-center'),
                _Rows(
                    _Html($template->title ?: __('cms::cms.untitled-email'))->class('text-sm font-medium text-gray-800'),
                    _Html(trans_choice('cms::cms.template-block-count', $blockCount, ['count' => $blockCount]))
                        ->class('text-xs text-gray-400 mt-0.5'),
                ),
            )->class('items-center gap-3 min-w-0 flex-1'),
            _Flex(
                _Button('cms::cms.use-template')
                    ->class('text-xs')
                    ->selfPost('createFromTemplate', ['template_id' => $template->id])
                    ->onSuccess(fn($e) => $e->closeModal()->run('() => { window.location.reload(); }')),
                _Link()->icon(_Sax('trash', 16))
                    ->class('text-gray-400 hover:text-red-600')
                    ->balloon('cms::cms.delete-template', 'down')
                    ->selfPost('deleteTemplate', ['template_id' => $template->id])
                    ->onSuccess(fn($e) => $e->selfGet('refreshGallery')->inPanel('template-gallery-panel')),
            )->class('items-center gap-2 flex-shrink-0'),
        )->class('p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50/30 transition-all');
    }

    public function createFromTemplate()
    {
        $template = PageModel::findOrFail(request('template_id'));
        $pageId = $this->targetPageId;

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
}
