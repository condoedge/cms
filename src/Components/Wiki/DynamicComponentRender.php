<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Kompo\Form;

class DynamicComponentRender extends Form
{
    public const AVAILABLE_COMPONENTS = [
        'article-page' => ArticlePage::class,
    ];

    protected $component;

    public function created()
    {
        $component = request()->route('know_component');
        $locale = request()->route('know_locale');

        if (!array_key_exists($component, self::AVAILABLE_COMPONENTS)) {
            abort(404);
        }

        $this->component = self::AVAILABLE_COMPONENTS[$component];

        session()->put('kompo_locale', $locale);
        app()->setLocale($locale);
    }

    public function render()
    {
        return new $this->component(request('id'), request()->all());
    }
}