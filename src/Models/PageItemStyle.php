<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Models\Abstracts\PageItemStyleModel;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;

class PageItemStyle extends PageItemStyleModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'page_item_styles';

    protected $casts = [
        'content' => \Anonimatrix\PageEditor\Casts\StylesCast::class,
    ];

    public function getAttribute($key)
    {
        return $this->content->{$key} ?? parent::getAttribute($key);
    }

    public function pageItem()
    {
        return $this->belongsTo(PageItem::class, 'page_item_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function beforeSave()
    {
        if(Features::hasFeature('teams'))
        {
            $this->team_id = auth()->user()->current_team_id;
        }
    }

    public function afterSave() { }

    public static function getGenericStylesOfType(string $class, $teamId = null)
    {
        if(!app('page-item-types')->contains($class)) {
            throw new \Exception("Class $class is not a valid page item type. Please check in the PageItemServiceProvider");
        }

        return static::where('block_type', $class::ITEM_NAME)
            ->when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->whereNull('page_item_id')
            ->whereNull('page_id')
            ->first();
    }

    public function save(array $options = [])
    {
        $this->beforeSave();
        $result = parent::save($options);
        $this->afterSave();

        return $result;
    }

    public function customForceDelete() //forceDelete wasn't working properly for some reason
    {
        \DB::statement("DELETE FROM ".$this->getTable()." WHERE id=".parent::getAttribute('id'));
    }
}