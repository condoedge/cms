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

    public function createTemplateFromPage($source, string $name)
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

        $this->replicatePageStyles($source, $template);
        $this->blockService->copyAllItemsToPage($source, $template);

        return $template;
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
