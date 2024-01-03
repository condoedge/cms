<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Anonimatrix\PageEditor\Models\Wiki\KnowledgeOpinion;
use Anonimatrix\PageEditor\Models\Wiki\KnowledgePage;
use Kompo\Form;

class ArticleOpinionForm extends Form
{
    public $containerClass = "";

    public $model = KnowledgePage::class;

    public function render()
    {
        $userActualOpinion = $this->model->actualUserOpinion();
        $userLike = $userActualOpinion && $userActualOpinion->type === KnowledgeOpinion::OPINION_LIKE;
        $userDislike = $userActualOpinion && $userActualOpinion->type === KnowledgeOpinion::OPINION_DISLIKE;

        return _FlexBetween(
            _Html('wiki.like-question')->class('text-lg'),
            _FlexBetween(
                _Link()->icon('thumb-up')->class('text-gray-300 text-4xl hover:text-green-600 ' . ($userLike ? 'text-green-600' : ''))
                    ->selfPost('likeArticle')->refresh(),
                _Link()->icon('thumb-down')->class('text-gray-300 text-4xl hover:text-red-600 ' . ($userDislike ? 'text-red-600' : ''))
                    ->selfPost('dislikeArticle')->refresh(),
            )->class('ml-4 gap-3'),
        )->class('bg-gray-100 rounded-lg px-8 py-4 mt-12 max-w-max gap-3 mx-auto');
    }

    
    public function likeArticle()
    {
        $actualOpinion = $this->model->actualUserOpinion();

        if (!$actualOpinion || $actualOpinion->type === KnowledgeOpinion::OPINION_DISLIKE) {
            $this->model->createOpinion(KnowledgeOpinion::OPINION_LIKE);
        }

        if($actualOpinion) $actualOpinion->delete();
    }

    public function dislikeArticle()
    {
        $actualOpinion = $this->model->actualUserOpinion();

        if (!$actualOpinion || $actualOpinion->type === KnowledgeOpinion::OPINION_LIKE) {
            $this->model->createOpinion(KnowledgeOpinion::OPINION_DISLIKE);
        }

        if($actualOpinion) $actualOpinion->delete();
    }
}