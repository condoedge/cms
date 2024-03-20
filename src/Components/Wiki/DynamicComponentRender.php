<?php

namespace Anonimatrix\PageEditor\Components\Wiki;

use Kompo\Form;

class DynamicComponentRender extends Form
{
    public const AVAILABLE_COMPONENTS = [
        ArticlePage::class,
    ];

    protected $component;

    public function created()
    {
        $component = request()->route('know_component');
        $locale = request()->route('know_locale');

        if (!in_array($component, self::AVAILABLE_COMPONENTS)) {
            abort(404);
        }

        $this->component = $component;

        session()->put('kompo_locale', $locale);
        app()->setLocale($locale);
    }

    public function render()
    {
        return new $this->component(request('id'), request()->all());
    }
}