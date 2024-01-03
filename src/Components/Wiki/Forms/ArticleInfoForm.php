<?php

namespace Anonimatrix\PageEditor\Components\Wiki\Forms;

use Anonimatrix\PageEditor\Components\Cms\PageInfoForm;
use Illuminate\Support\Facades\Route;

class ArticleInfoForm extends PageInfoForm
{
    public function beforeSave()
    {
        $this->model->associated_route = request('associated_route');
        $this->model->group_type = 'knowledge';
    }

    public function afterSave()
    {
        $this->model->tags()->sync(
            array_merge(request('subcategories_ids', []), request('categories_ids', [])),
        );
    }

    public function extraInputs()
    {
        return _Rows(
            _Input('wiki.page-exterior-color')->type('color')->value($this->model->getExteriorBackgroundColor())->name('exterior_background_color'),
            _Input('wiki.subtitle')->name('subtitle'),
            _Select('wiki.linked-route')->options(
                collect(Route::getRoutes()->getRoutesByName())->mapWithKeys(fn($route, $name) => [$name => $name]),
            )->name('associated_route'),
            new ArticleCategoriesForm($this->model->id),
        );
    }
}