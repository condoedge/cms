<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use App\Models\Teams\Team;
use App\Models\User;


class Page extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Kompo\Database\HasTranslations;
    use \App\Models\Traits\PageCopyTrait;

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
        return $this->belongsTo(Team::class);
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

    public function getContentBackgroundColor()
    {
        return $this->content_background_color ?: '#FFFFFF';
    }

    public function getExteriorBackgroundColor()
    {
        return $this->exterior_background_color ?: '#ECEEF2';
    }

    public function getTextColor()
    {
        return $this->text_color ?: '#000000';
    }

    public function getTitleColor()
    {
        return $this->title_color ?: '#000000';
    }

    public function getLinkColor()
    {
        return $this->link_color ?: '#003AB3';
    }

    public function getButtonColor()
    {
        return $this->button_color ?: '#003AB3';
    }

    public function getFontSize()
    {
        return $this->font_size ?: 12;
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
        return auth()->user()->isTeamOwner();
    }

}
