<?php

namespace Anonimatrix\PageEditor\Models\Wiki\;

use Anonimatrix\PageEditor\Models\Page;

class KnowledgePage extends Page
{
    public function opinions()
    {
        return $this->hasMany(KnowledgeOpinion::class, 'page_id');
    }

    public function likes()
    {
        return $this->opinions()->where('type', KnowledgeOpinion::OPINION_LIKE);
    }

    public function dislikes()
    {
        return $this->opinions()->where('type', KnowledgeOpinion::OPINION_DISLIKE);
    }

    public function views()
    {
        return $this->hasMany(KnowledgeView::class, 'page_id');
    }

    public static function whatsNewUnreadedCount()
    {
        return static::where('group_type', 'knowledge')->where('associated_route', 'knowledge.whats-new')
        ->whereDoesntHave('views', function($q) {
            $q->where('user_id', auth()->user()?->id ?? 0);
        })
        ->count();
    }

    public function actualUserOpinion($type = null)
    {
        return $this->opinions()->where(function ($q) {
            $q->where('ip', request()->ip())
                ->orWhere('user_id', auth()->user()?->id);
        })->when($type, fn($q) => $q->where('type', $type))->first();
    }

    public function createOpinion($type)
    {
        $this->actualUserOpinion()?->delete();

        $like = new KnowledgeOpinion();

        $like->user_id = auth()->user()?->id;
        $like->page_id = $this->id;
        $like->type = $type;
        $like->ip = request()->ip();

        $this->opinions()->save($like);
    }
}