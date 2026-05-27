<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Anonimatrix\PageEditor\Support\Facades\PageStyle;
use Kompo\Form;

class PageStylingForm extends Form
{
    protected $pageId;

    public function created()
    {
        $this->model(PageModel::find($this->modelKey()) ?? PageModel::make());
    }

    public function handle()
    {
        $styleModel = $this->model->styles ?? PageItemStyleModel::make();
        PageStyle::setStylesToModel($styleModel);

        $this->model->styles()->save($styleModel);

        $this->model->exterior_background_color = request('exterior_background_color', $this->model->getExteriorBackgroundColor());
        $this->model->save();
    }

    public function render()
    {
        return _Rows(
            $this->colorsGroup(),
            $this->typographyGroup(),
            $this->layoutGroup(),
            $this->submitSection(),
        );
    }

    protected function colorsGroup()
    {
        return _Rows(
            _Html('cms::cms.colors')->class('vlStyleLabel mb-3'),

            _Rows(
                _Html('cms::cms.content-background-color')->class('vlStyleSubLabel'),
                _Flex(
                    _Div()->class('w-7 h-9 rounded-md border border-gray-200 flex-shrink-0')->style('background:' . $this->model->getContentBackgroundColor()),
                    _Input()->type('color')->value($this->model->getContentBackgroundColor())->name('background-color', false)
                        ->class('vlColorInput'),
                )->class('flex items-center gap-2'),
            )->class('mb-2.5'),

            _Rows(
                _Html('cms::cms.exterior-background-color')->class('vlStyleSubLabel'),
                _Flex(
                    _Div()->class('w-7 h-9 rounded-md border border-gray-200 flex-shrink-0')->style('background:' . $this->model->getExteriorBackgroundColor()),
                    _Input()->type('color')->value($this->model->getExteriorBackgroundColor())->name('exterior_background_color')
                        ->class('vlColorInput'),
                )->class('flex items-center gap-2'),
            )->class('mb-2.5'),

            _Rows(
                _Html('cms::cms.text-color')->class('vlStyleSubLabel'),
                _Flex(
                    _Div()->class('w-7 h-9 rounded-md border border-gray-200 flex-shrink-0')->style('background:' . $this->model->getTextColor()),
                    _Input()->type('color')->value($this->model->getTextColor())->name('color', false)
                        ->class('vlColorInput'),
                )->class('flex items-center gap-2'),
            )->class('mb-2.5'),

            _Rows(
                _Html('cms::cms.link-color')->class('vlStyleSubLabel'),
                _Flex(
                    _Div()->class('w-7 h-9 rounded-md border border-gray-200 flex-shrink-0')->style('background:' . $this->model->getLinkColor()),
                    _Input()->type('color')->value($this->model->getLinkColor())->name('link-color', false)
                        ->class('vlColorInput'),
                )->class('flex items-center gap-2'),
            ),
        )->class('p-4 border-b border-gray-100');
    }

    protected function typographyGroup()
    {
        return _Rows(
            _Html('cms::cms.typography')->class('vlStyleLabel mb-3'),

            _Rows(
                _Html('cms::cms.font-family')->class('vlStyleSubLabel'),
                _Select()->name('font-family', false)
                    ->options($this->fontFamilyOptions())
                    ->default($this->model->getStyleProperty('font_family') ?: 'system'),
            )->class('mb-2.5'),

            _Rows(
                _Html('cms::cms.font-size')->class('vlStyleSubLabel'),
                _Flex(
                    _InputNumber()->value($this->model->getFontSize())->name('font-size', false),
                    _Html('px')->class('text-sm text-gray-400 font-medium'),
                )->class('items-center gap-2'),
            ),
        )->class('p-4 border-b border-gray-100');
    }

    protected function layoutGroup()
    {
        return _Rows(
            _Html('cms::cms.layout')->class('vlStyleLabel mb-3'),

            _Rows(
                _Html('cms::cms.content-max-width')->class('vlStyleSubLabel'),
                _Flex(
                    _InputNumber()->value($this->model->getContentMaxWidth())->name('content-max-width', false),
                    _Html('px')->class('text-sm text-gray-400 font-medium'),
                )->class('items-center gap-2'),
            ),
        )->class('p-4 border-b border-gray-100');
    }

    protected function submitSection()
    {
        return _Rows(
            _SubmitButton('cms::cms.apply-design')
                ->class('w-full')
                ->alert('cms::cms.saved-successfully')
                ->onSuccess(fn($e) => $e->run('() => {
                    setTimeout(() => window.location.reload(), 500);
                }')),
        )->class('p-4');
    }

    protected function fontFamilyOptions()
    {
        return [
            'system' => 'System Default',
            'arial' => 'Arial',
            'helvetica' => 'Helvetica',
            'georgia' => 'Georgia',
            'times' => 'Times New Roman',
            'verdana' => 'Verdana',
            'trebuchet' => 'Trebuchet MS',
            'tahoma' => 'Tahoma',
        ];
    }

}
