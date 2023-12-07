<?php

namespace Anonimatrix\PageEditor\Models;

use Anonimatrix\PageEditor\Support\Facades\Features;
use Anonimatrix\PageEditor\Support\Facades\Teams;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Page extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Kompo\Database\HasTranslations;
    use \Anonimatrix\PageEditor\Traits\PageCopyTrait;

    protected $casts = [
        'published_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $translatable = [
        'title',
        'permalink',
    ];

	/* RELATIONS */
    public function user() //the author
    {
        return $this->belongsTo(User::class);
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
        return $this->exterior_background_color ?: '#ECEEF2';
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
                    _Link()->icon(_Sax('clipboard', 20))->class('text-gray-400 pr-2')
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

    public function deletable()
    {
        //return auth()->user()->isTeamOwner();
        return true;
    }

}
