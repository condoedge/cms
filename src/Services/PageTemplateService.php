<?php

namespace Anonimatrix\PageEditor\Services;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;

class PageTemplateService
{
    public function __construct(
        protected PageBlockService $blockService,
    ) {
    }

    /**
     * Single source of truth for listing reusable templates (is_template = true).
     * Encapsulates the team/global filtering + ordering that the two template
     * UIs (apply-to-page gallery, create-from-template chooser) previously
     * duplicated inline with divergent rules.
     *
     * Scopes (parameterized because the two UIs intentionally differ):
     *  - 'all'    : combined global + team list, ordered by updated_at desc.
     *               When $teamId is given, limits to (team_id = $teamId OR global);
     *               when null, returns every template (no team filter).
     *               => TemplateGallery (apply a template to the current page).
     *  - 'global' : non-team templates only (team_id IS NULL), ordered by order,id.
     *               => PageTemplateChooser "Quick start" section.
     *  - 'team'   : a specific team's templates (team_id = $teamId), updated_at desc.
     *               => PageTemplateChooser "My templates" section.
     *
     * $teamId is supplied by the caller so the existing team-id source per UI is
     * preserved verbatim (no change to which value identifies "my team").
     */
    public function listableTemplates(string $scope = 'all', $teamId = null)
    {
        $query = PageModel::where('is_template', true);

        if ($scope === 'global') {
            return $query->whereNull('team_id')
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        }

        if ($scope === 'team') {
            return $query->where('team_id', $teamId)
                ->orderByDesc('updated_at')
                ->get();
        }

        // 'all' — combined list. Only constrain by team when a team id is given
        // (teams feature on); otherwise list all templates.
        if (!is_null($teamId)) {
            $query->where(function ($q) use ($teamId) {
                $q->where('team_id', $teamId)
                  ->orWhereNull('team_id');
            });
        }

        return $query->orderByDesc('updated_at')->get();
    }

    /**
     * Spawn a fresh newsletter Page for the current team / user. If a template
     * id is given, the template's blocks + styles are applied to the new page
     * (immutable-fork semantics — the source template is not touched).
     *
     * The new page's title defaults to either the template title or a generic
     * "Untitled" placeholder; the user can rename it from the editor.
     */
    public function createNewsletterFromTemplate(?int $templateId = null, ?string $title = null)
    {
        $template = $templateId
            ? PageModel::where('is_template', true)->findOrFail($templateId)
            : null;

        $page = PageModel::make();
        $page->title = $title
            ?? ($template?->title ? __('cms::cms.untitled-from-template', ['name' => $template->title]) : __('cms::cms.untitled-newsletter'));
        $page->is_template = false;
        $page->published_at = null;
        $page->sent_at = null;
        $page->save();

        if ($template) {
            $this->applyTemplateToPage($template, $page);
        }

        return $page;
    }

    public function createTemplateFromPage($source, string $name, bool $global = false)
    {
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

        if ($global) {
            $this->makeTemplateGlobal($template);
        }

        $this->replicatePageStyles($source, $template);
        $this->blockService->copyAllItemsToPage($source, $template);

        return $template;
    }

    /**
     * Promote a template to GLOBAL scope (team_id NULL → visible to every team).
     * Uses a query-builder update to BYPASS Page::beforeSave, which re-stamps the
     * current team_id on every model save (so a normal save could never produce a
     * global template). Authorization is the CALLER's responsibility — only a
     * super-admin should be allowed to invoke this.
     */
    public function makeTemplateGlobal($template): void
    {
        $id = is_object($template) ? $template->id : $template;

        PageModel::where('id', $id)
            ->where('is_template', true)
            ->update(['team_id' => null]);

        if (is_object($template)) {
            $template->team_id = null;
        }
    }

    public function applyTemplateToPage($template, $targetPage): void
    {
        $targetPage->pageItems()->get()->each->delete();

        $this->blockService->copyAllItemsToPage($template, $targetPage);

        $this->syncPageStyles($template, $targetPage);
    }

    protected function replicatePageStyles($source, $target): void
    {
        if (!$source->styles) return;

        $newStyles = $source->styles->replicate();
        $newStyles->page_id = $target->id;
        $newStyles->save();
    }

    protected function syncPageStyles($source, $target): void
    {
        if (!$source->styles) return;

        if ($target->styles) {
            $target->styles->content = $source->styles->content;
            $target->styles->save();
            return;
        }

        $this->replicatePageStyles($source, $target);
    }
}
