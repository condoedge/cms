<?php

namespace Anonimatrix\PageEditor\Components\Wiki\Forms;

use Anonimatrix\PageEditor\Models\Tags\Tag;
use Kompo\Modal;

class ArticlesTagsForm extends Modal
{
    public $model = Tag::class;

    public $_Title = 'wiki.tags';

    public function beforeSave()
    {
        $this->model->tag_id = request('tag_id');
    }

    public function body()
    {
        return _Rows(
            _Hidden()->name('tag_type')->value(Tag::TAG_TYPE_PAGE),
            _Input('wiki.category-name')->name('name'),
            _ButtonGroup('wiki.tag-type')
                ->optionClass('px-4 py-2 text-center cursor-pointer')
                ->selectedClass('bg-level3 text-white font-medium', 'bg-gray-200 text-level3 font-medium')
                ->class('mb-2')->options([
                    'categorie' => 'wiki.category',
                    'subcategorie' => 'wiki.subcategory',
                ])->name('type', false)->default('categorie')->selfGet('getTypeInputs')->inPanel('type_inputs'),
            _Panel()->id('type_inputs'),
            _FlexEnd(
                _SubmitButton('wiki.save')->closeModal()->refresh(ArticleCategoriesForm::ID),
            )->class('mt-4'),
        );
    }

    public function getTypeInputs()
    {
        if(request('type') == 'categorie') return null;

        return _Select('wiki.category-parent')->options(
            Tag::forPage()->categories()->pluck('name','id'),
        )->name('tag_id')->class('mt-2');
    }

    public function rules()
    {
        return [
            'name' => 'required',
            'tag_type' => 'required',
            'tag_id' => 'required_if:type,subcategorie',
        ];
    }
}