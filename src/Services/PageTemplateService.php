<?php

namespace Anonimatrix\PageEditor\Services;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;

class PageTemplateService
{
    public function __construct(
        protected PageBlockService $blockService,
    ) {
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
