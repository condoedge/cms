<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageTemplateService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

/**
 * Reusable entry-point for newsletter/page creation: pick a starter template
 * (or start blank), then redirect to the editor.
 *
 * Two sections:
 *   1. Quick start  — Blank + global templates (is_template=true, team_id IS NULL).
 *   2. My templates — team-saved templates (is_template=true, team_id=currentTeamId()),
 *      rendered only when at least one exists.
 *
 * Selecting a card creates a fresh Page (via PageTemplateService) and redirects
 * to the editor. Host projects extend this and override the project-specific
 * hooks below; the generic flow stays in the package so every project can reuse it.
 */
class PageTemplateChooser extends Modal
{
    public $id = 'new-newsletter-chooser';

    // Wider than the base Modal's max-w-xl — the body is a 2-column card grid.
    public $class = 'overflow-y-auto mini-scroll max-w-3xl';

    public $_Title = 'cms::cms.choose-template';

    protected $noHeaderButtons = true;

    /* HOOKS — override in host projects */

    // Translation key for the badge shown on global (non-team) templates.
    // Override per project, e.g. 'cms::cms.coolecto-template'.
    protected function globalBadgeLabel(): string
    {
        return 'cms::cms.global-template';
    }

    // Where to send the user once the page is created. Defaults to the editor
    // route convention used across the ecosystem.
    protected function redirectAfterCreate($page)
    {
        return redirect()->route('page.form', ['id' => $page->id]);
    }

    /* BODY */

    public function body()
    {
        $globals = $this->globalTemplates();
        $teamTemplates = $this->teamTemplates();

        return [
            _Html('cms::cms.choose-template-desc')->class('text-sm text-gray-500 mb-4'),

            _Html('cms::cms.quick-start')->class('text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2'),
            _Rows(
                $this->blankCard(),
                ...$globals->map(fn ($t) => $this->templateCard($t, isGlobal: true))->all(),
            )->class('grid grid-cols-1 md:grid-cols-2 gap-3 mb-5'),

            $teamTemplates->isEmpty() ? null : _Html('cms::cms.my-templates')->class('text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2 mt-2'),
            $teamTemplates->isEmpty() ? null : _Rows(
                ...$teamTemplates->map(fn ($t) => $this->templateCard($t, isGlobal: false))->all(),
            )->class('grid grid-cols-1 md:grid-cols-2 gap-3'),
        ];
    }

    protected function blankCard()
    {
        return _Rows(
            _Rows(
                _Html()->icon(_Sax('document', 32))->class('text-gray-400'),
            )->class('vlChooserThumb bg-gray-50 border-2 border-dashed border-gray-300 flex items-center justify-center'),
            _Rows(
                _Html('cms::cms.blank-newsletter')->class('text-sm font-semibold text-gray-900'),
                _Html('cms::cms.blank-newsletter-desc')->class('text-xs text-gray-500 mt-1'),
            )->class('p-3'),
        )
            ->class('vlChooserCard cursor-pointer rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition-all overflow-hidden')
            ->selfPost('createPage', ['template_id' => null])
            ->redirect();
    }

    protected function templateCard($template, bool $isGlobal)
    {
        $thumbnailUrl = $template->template_thumbnail
            ? asset($template->template_thumbnail)
            : null;

        $blockCount = $template->orderedMainPageItems()->count();

        $badgeLabel = $isGlobal ? $this->globalBadgeLabel() : 'cms::cms.team-template';
        $badgeClass = $isGlobal
            ? 'bg-blue-50 text-blue-700'
            : 'bg-green-50 text-green-700';

        return _Rows(
            _Rows(
                $thumbnailUrl
                    ? _Html('<img src="' . e($thumbnailUrl) . '" alt="" style="width:100%;height:100%;object-fit:cover;display:block;" />')
                    : _Html()->icon(_Sax('document-text', 32))->class('text-gray-300'),
            )->class('vlChooserThumb bg-gray-50 flex items-center justify-center overflow-hidden'),
            _Rows(
                _FlexBetween(
                    _Html($template->title ?: __('cms::cms.untitled-template'))->class('text-sm font-semibold text-gray-900'),
                    $isGlobal ? null : _Flex(
                        // Super-admins can promote a team template to GLOBAL (all
                        // teams). Gated again server-side in makeTemplateGlobal().
                        $this->canMakeGlobal() ? _Link()->icon(_Sax('cloud-add', 14))
                            ->class('text-gray-300 hover:text-blue-600 shrink-0')
                            ->balloon('cms::cms.make-template-global', 'down')
                            ->selfPost('makeTemplateGlobal', ['template_id' => $template->id])
                            ->refresh($this->id) : null,
                        _Link()->icon(_Sax('trash', 14))
                            ->class('text-gray-300 hover:text-red-600 shrink-0')
                            ->balloon('cms::cms.delete-template', 'down')
                            ->selfPost('deleteTeamTemplate', ['template_id' => $template->id])
                            ->refresh($this->id),
                    )->class('items-center gap-2 shrink-0'),
                )->class('items-start gap-2'),
                _Html('<span class="inline-block text-[10px] font-medium px-2 py-0.5 rounded-full ' . $badgeClass . '">' . e(__($badgeLabel)) . '</span>')
                    ->class('mt-1'),
                _Html(trans_choice('cms::cms.template-block-count', $blockCount, ['count' => $blockCount]))
                    ->class('text-xs text-gray-400 mt-1'),
            )->class('p-3'),
        )
            ->class('vlChooserCard cursor-pointer rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition-all overflow-hidden')
            ->selfPost('createPage', ['template_id' => $template->id])
            ->redirect();
    }

    public function createPage()
    {
        $templateId = request('template_id');
        $templateId = $templateId === '' || $templateId === null ? null : (int) $templateId;

        $page = app(PageTemplateService::class)->createNewsletterFromTemplate($templateId);

        return $this->redirectAfterCreate($page);
    }

    public function deleteTeamTemplate()
    {
        $template = PageModel::where('is_template', true)
            ->where('team_id', currentTeamId())
            ->findOrFail(request('template_id'));

        $template->forceDelete();
    }

    public function makeTemplateGlobal()
    {
        if (!$this->canMakeGlobal()) return;

        $template = PageModel::where('is_template', true)->findOrFail(request('template_id'));

        app(PageTemplateService::class)->makeTemplateGlobal($template);
    }

    protected function canMakeGlobal(): bool
    {
        return function_exists('isSuperAdmin') && isSuperAdmin();
    }

    protected function globalTemplates()
    {
        return app(PageTemplateService::class)->listableTemplates('global');
    }

    protected function teamTemplates()
    {
        return app(PageTemplateService::class)->listableTemplates('team', currentTeamId());
    }
}
