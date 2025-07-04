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
            _Html('cms::cms.style')->miniTitle()->class('mb-4'),
            _Rows(
                _InputNumber('cms::cms.font-size')->value($this->model->getFontSize())->name('font-size', false),
                _Columns(
                    _Input('cms::cms.content-background-color')->type('color')->value($this->model->getContentBackgroundColor())->name('background-color', false),
                    _Input('cms::cms.exterior-background-color')->type('color')->value($this->model->getExteriorBackgroundColor())->name('exterior_background_color'),
                ),
                _Columns(
                    _Input('cms::cms.text-color')->type('color')->value($this->model->getTextColor())->name('color', false),
                ),
                _Columns(
                    _Input('cms::cms.link-color')->type('color')->value($this->model->getLinkColor())->name('link-color', false),
                ),
            ),
            _FlexEnd(
                _SubmitButton('cms::cms.save')->class('mt-4'),
            ),
        );
    }
}
