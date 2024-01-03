<?php

namespace Anonimatrix\PageEditor\Models\Wiki;

use Illuminate\Database\Eloquent\Model;

class KnowledgeView extends Model
{
    protected $fillable = [
        'user_id',
        'page_id',
        'ip',
    ];

    public function knowledge()
    {
        return $this->belongsTo(KnowledgePage::class, 'page_id');
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}