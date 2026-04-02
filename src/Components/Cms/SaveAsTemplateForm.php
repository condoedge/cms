<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;

class SaveAsTemplateForm extends Modal
{
    public $id = 'save-as-template-form';

    public $_Title = 'cms::cms.save-as-template';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::findOrFail($this->modelKey()));
    }

    public function headerButtons()
    {
        return _SubmitButton('cms::cms.save-as-template')
            ->selfPost('saveAsTemplate')
            ->withAllFormValues()
            ->alert('cms::cms.template-saved')
            ->closeModal();
    }

    public function body()
    {
        return [
            _Html('cms::cms.save-as-template-desc')->class('text-sm text-gray-500 mb-4'),

            _Input('cms::cms.template-name')
                ->name('template_name')
                ->value($this->model->title ? $this->model->title . ' - Template' : ''),
        ];
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
}
