<?php

namespace Anonimatrix\PageEditor\Models\Tags;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Taggable extends EloquentModel
{
	protected $table = 'taggable_tag';

	/* RELATIONS */
	public function tag()
	{
		return $this->belongsTo(Tag::class);
	}

    public function taggable()
    {
        return $this->morphTo();
    }
}
