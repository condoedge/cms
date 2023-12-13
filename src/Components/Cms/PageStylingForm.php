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
    }

    public function render()
    {
        return _Rows(
            _Html('campaign.style')->miniTitle()->class('mb-4'),
            _Rows(
                _InputNumber('campaign.font-size')->value($this->model->getFontSize())->name('font-size', false),
                _Columns(
                    _Input('campaign.content-background-color')->type('color')->value($this->model->getContentBackgroundColor())->name('background-color', false),
                    _Input('campaign.exterior-background-color')->type('color')->value($this->model->getExteriorBackgroundColor())->name('exterior_background_color'),
                ),
                _Columns(
                    _Input('campaign.text-color')->type('color')->value($this->model->getTextColor())->name('color', false),
                ),
                _Columns(
                    _Input('campaign.link-color')->type('color')->value($this->model->getLinkColor())->name('link-color', false),
                ),
            ),
            _FlexEnd(
                _SubmitButton('translate.save')->class('mt-4'),
            ),
        );
    }
}
