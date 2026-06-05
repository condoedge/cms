<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageTemplateService;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

class TemplateGallery extends Modal
{
    public $id = 'template-gallery';

    public $_Title = 'cms::cms.template-gallery';

    // Match the chooser's widened modal; the body is a 2-column card grid.
    public $class = 'overflow-y-auto mini-scroll max-w-3xl';

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
        // Combined global + team list. Pass the team id only when teams are on so
        // the service applies the (team OR global) constraint; otherwise list all.
        $teamId = Features::hasFeature('teams') ? auth()->user()->current_team_id : null;

        return app(PageTemplateService::class)->listableTemplates('all', $teamId);
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
            ...$templates->map(fn($template) => $this->templateCard($template))->all(),
        )->class('grid grid-cols-1 md:grid-cols-2 gap-3');
    }

    protected function templateCard($template)
    {
        $blockCount = $template->orderedMainPageItems()->count();

        $thumbnailUrl = $template->template_thumbnail
            ? asset($template->template_thumbnail)
            : null;

        return _Rows(
            _Rows(
                $thumbnailUrl
                    ? _Html('<img src="' . e($thumbnailUrl) . '" alt="" />')
                    : _Html()->icon(_Sax('document-text', 32))->class('text-gray-300'),
            )->class('vlChooserThumb bg-gray-50 flex items-center justify-center overflow-hidden'),
            _Rows(
                _FlexBetween(
                    _Html($template->title ?: __('cms::cms.untitled-template'))->class('text-sm font-semibold text-gray-900 truncate'),
                    _Link()->icon(_Sax('trash', 14))
                        ->class('text-gray-300 hover:text-red-600 shrink-0')
                        ->balloon('cms::cms.delete-template', 'down')
                        ->selfPost('deleteTemplate', ['template_id' => $template->id])
                        ->refresh(),
                )->class('items-start gap-2'),
                _Html(trans_choice('cms::cms.template-block-count', $blockCount, ['count' => $blockCount]))
                    ->class('text-xs text-gray-400 mt-0.5'),
                _Button('cms::cms.use-template')
                    ->class('mt-3 w-full')
                    ->selfPost('createFromTemplate', ['template_id' => $template->id])
                    ->onSuccess(fn($e) => $e->closeModal()->run('() => { window.location.reload(); }')),
            )->class('p-3'),
        )->class('vlChooserCard rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition-all overflow-hidden');
    }

    public function createFromTemplate()
    {
        if (!$this->targetPageId) return;

        $template = PageModel::findOrFail(request('template_id'));
        $targetPage = PageModel::findOrFail($this->targetPageId);

        app(PageTemplateService::class)->applyTemplateToPage($template, $targetPage);
    }

    public function deleteTemplate()
    {
        $template = PageModel::findOrFail(request('template_id'));

        if ($template->is_template) {
            $template->forceDelete();
        }
    }
}
