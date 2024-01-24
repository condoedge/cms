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
        $component = request()->route('component');

        if (!in_array($component, self::AVAILABLE_COMPONENTS)) {
            abort(404);
        }

        $this->component = $component;
    }

    public function render()
    {
        return new $this->component(request('id'), request()->all());
    }
}