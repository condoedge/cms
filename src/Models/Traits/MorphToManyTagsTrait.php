<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Anonimatrix\PageEditor\Models\Tags\Tag;

trait MorphToManyTagsTrait
{
	/* RELATIONS */
    public function tags()
	{
		return $this->morphToMany(Tag::class, 'taggable', 'taggable_tag')->withTimestamps();
	}

    public function scopeForTags($query, $tagsIds)
    {
        return $query->whereHas(
            'tags', fn($q) => $q->whereIn('tags.id', $tagsIds)
        );
    }

    public function scopeOrForTags($query, $tagsIds)
    {
        return $query->orWhereHas(
            'tags', fn($q) => $q->whereIn('tags.id', $tagsIds)
        );
    }
}
