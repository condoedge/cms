<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Models\Abstracts\PageModel;
use Anonimatrix\PageEditor\Models\Traits\HasPackageFactory;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Features\Teams;

class Page extends PageModel
{
    use HasPackageFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Kompo\Database\HasTranslations;
    use \Anonimatrix\PageEditor\Models\Traits\PageCopyTrait;
    use \Anonimatrix\PageEditor\Models\Traits\MorphToManyTagsTrait;

    protected $table = 'pages';

    protected $casts = [
        'published_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $translatable = [
        'title',
        'permalink',
    ];

    public function beforeSave()
    {
        $this->user_id = auth()->id();

        if (Features::hasFeature('teams')) {
            $this->team_id = auth()->user()->current_team_id;
        }

        if(!$this->group_type){
            $this->group_type = config('page-editor.default_page_group_type');
        }
    }

    public function afterSave() {}

	/* RELATIONS */
    public function user() //the author
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function team()
    {
        if(!Features::hasFeature('teams')) {
            return null;
        }

        return $this->belongsTo(Teams::getTeamClass());
    }

    public function parentPage()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function pageItems()
    {
        return $this->hasMany(PageItem::class);
    }

    public function orderedMainPageItems()
    {
        return $this->pageItems()->notLinked()->orderBy('order');
    }

    public function styles()
    {
        return $this->hasOne(PageItemStyle::class, 'page_id', 'id');
    }

    /* CALCULATED FIELDS */
    public function getHtmlContent($variables = [])
    {
        return $this->orderedMainPageItems()->get()->reduce(function($html, $pageItem) use ($variables){
            $pageItemType = $pageItem->getPageItemType();
            $pageItemType?->setVariables($variables);

            return $html . ($pageItemType?->toHtmlWrap() ?: '');
        }, '');
    }

    public function getPreview($variables = [])
    {
        return $this->orderedMainPageItems()->get()->reduce(function($els, $pageItem) use ($variables) {
            $pageItemType = $pageItem->getPageItemType();
            $pageItemType?->setVariables($variables);

            return $els->push($pageItemType?->toElementWrap() ?: '');
        }, collect());
    }

    public function getPreviewWithSelfGetInPanel($selfGet, $panel)
    {
        return $this->orderedMainPageItems()->get()->reduce(function($els, $pageItem) use ($selfGet, $panel) {
            $el = $pageItem->getPageItemType()->toElementWrap();

            $completeEl = _Rows($el)->onClick->selfGet($selfGet, ['item_id' => $pageItem->id, 'page_id' => $pageItem->page_id])->inPanel($panel);

            return $els->push($completeEl);
        }, collect());
    }

    public function getExteriorBackgroundColor()
    {
        return $this->exterior_background_color ?: '#F8FAFC';
    }

    public function getStyleProperty($property)
    {
        return $this->styles?->content?->$property;
    }

    public function getContentBackgroundColor()
    {
        return $this->getStyleProperty('background_color') ?: '#FFFFFF';
    }

    public function getTextColor()
    {
        return $this->getStyleProperty('color') ?: '#000000';
    }

    public function getLinkColor()
    {
        return $this->getStyleProperty('link_color') ?: '#003AB3';
    }

    public function getFontSize()
    {
        return $this->getStyleProperty('font_size_raw') ?: 12;
    }

    /* ELEMENTS */
    public function adminBlock($refreshId = '')
    {
        $lastModif = _Html($this->updated_at->translatedFormat('d M Y H:i'))
            ->class('text-xs text-left w-36 pr-4')
            ->class('block');

        $sentAt = _Html($this->sent_at?->translatedFormat('d M Y H:i') ?: 'newsletter.not-published')
            ->class('text-xs text-left w-36 pr-4')
            ->class('block');

        return _FlexBetween(
            _Flex4(
                _Html()->icon('selector')->class('dragHandle text-2xl text-transparent group-hover:text-gray-400'),
                _Html($this->order + 1)->class('w-6 text-gray-400'),
                _Html($this->title),
            ),
            _FlexEnd4(
                $sentAt,
                $lastModif,
                _Flex(
                    _Link()->class('text-gray-400 pr-2')
                        ->selfGet('duplicatePage', ['id' => $this->id])
                        ->refresh($refreshId),
                    _DeleteLink()->byKey($this),
                )->class('w-20 pr-2'),
            )->class('text-gray-400')
        )->class('group');
    }

    /* ACTIONS */
    public function delete()
    {
        $this->pageItems()->get()->each->delete();

        $this->pages()->get()->each->delete();

        parent::delete();
    }

    public function forceDelete()
    {
        $this->pageItems()->withTrashed()->get()->each->forceDelete();

        $this->pages()->withTrashed()->get()->each->forceDelete();
        $this->styles()->withTrashed()->first()?->customForceDelete();

        $this->customForceDelete();
    }

    public function customForceDelete() //forceDelete wasn't working properly for some reason
    {
        \DB::statement("DELETE FROM ".$this->getTable()." WHERE id=".$this->id);
    }

    public function deletable()
    {
        //return auth()->user()->isTeamOwner();
        return true;
    }

    public function save(array $options = [])
    {
        $this->beforeSave($this);
        $result = parent::save($options);
        $this->afterSave($this);

        return $result;
    }
}
