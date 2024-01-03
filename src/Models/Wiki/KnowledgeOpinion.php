<?php

namespace Anonimatrix\PageEditor\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

class KnowledgeOpinion extends Model
{
    public const OPINION_LIKE = 'like';
    public const OPINION_DISLIKE = 'dislike';
    
    public function knowledge()
    {
        return $this->belongsTo(KnowledgePage::class, 'page_id');
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}