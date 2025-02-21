<?php

namespace Anonimatrix\PageEditor\Models\Tags;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
	use \Kompo\Database\HasTranslations;

	protected $translatable = ['name', 'description'];

	public const TAG_TYPE_PAGE  = 100;

    public const TAG_CONTEXT_ALL = 1;

	/* RELATIONS */
	public function taggables()
	{
		return $this->hasMany(Taggable::class);
	}

	/* SCOPES */
	public function scopeForPage($query)
	{
		return $query->where('tag_type', self::TAG_TYPE_PAGE);
	}

	public function scopeCategories($query)
	{
		return $query->whereNull('tags.tag_id');
	}

	public function scopeSubcategories($query, $tagId = null)
	{
		return $query->when($tagId && is_array($tagId), fn($q) => $q->whereIn('tags.tag_id', $tagId))
			->when($tagId && !is_array($tagId), fn($q) => $q->where('tags.tag_id', $tagId))
			->whereNotNull('tags.tag_id');
	}

	/* ELEMENTS */

	/* ACTIONS */
	public function addTaggable($taggableId, $taggableType)
	{
		$taggable = new Taggable();
		$taggable->taggable_id = $taggableId;
		$taggable->taggable_type = $taggableType;
		$taggable->tag_id = $this->id;
		$taggable->save();
	}

	public function deletable()
	{
		return auth()->user()->isCmsAdmin() || (Features::hasFeature('teams') && auth()->user() && $this->team_id == auth()->user()->current_team_id);
	}

	public function editable()
	{
		return $this->deletable();
	}


	public function save(array $options = [])
	{
		$this->tag_type = self::TAG_TYPE_PAGE;

		parent::save($options);
	}
}
