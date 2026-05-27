<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\PageBlockService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageItemModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Kompo\Form;

class CopyBlockForm extends Form
{
    public $id = 'copy-block-form';

    protected $pageId;

    public function created()
    {
        $this->pageId = (int) $this->prop('page_id');
    }

    public function render()
    {
        $service = app(PageBlockService::class);
        $pageOptions = $service->copyableSourcePages($this->pageId)
            ->mapWithKeys(fn($p) => [$p->id => $p->title])
            ->toArray();

        return _Rows(
            _Flex(
                _Html('cms::cms.copy-block-from-newsletter')->class('font-semibold text-sm'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { if (window.vlPageEditor) vlPageEditor.closeDrawer() }'),
            )->class('justify-between items-center mb-4'),
            _Select('cms::cms.select-newsletter')->name('select_newsletter', false)
                ->options($pageOptions)
                ->selfGet('getBlockOptions')->inPanel('copy-block-items-panel'),
            _Panel()->id('copy-block-items-panel')->class('mt-3'),
        )->class('p-4');
    }

    public function getBlockOptions()
    {
        $sourcePageId = (int) request('select_newsletter');
        if (!$sourcePageId) return _Html('');

        $options = app(PageBlockService::class)->copyableItemOptions($sourcePageId);

        return _Rows(
            _Select('cms::cms.select-block')->name('select_block', false)
                ->options($options),
            _Button('cms::cms.copy-this-block')->icon('duplicate')
                ->selfPost('copyBlock')->withAllFormValues()
                ->onSuccess(fn($e) => $e->run('() => { if (window.vlPageEditor) vlPageEditor.refreshPreview() }'))
                ->class('mt-3 w-full'),
        );
    }

    public function copyBlock()
    {
        $sourceItem = PageItemModel::findOrFail(request('select_block'));
        $page = PageModel::findOrFail($this->pageId);

        $newItem = app(PageBlockService::class)->copyToPage($sourceItem, $page);

        return response()->json(['id' => $newItem->id]);
    }
}
