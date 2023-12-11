<?php

namespace Anonimatrix\PageEditor\Services;

class PageStyleService
{
    protected $automaticStyles;

    public function __construct($autoStyles = [])
    {
        $this->automaticStyles = $autoStyles;
    }

    public function setStylesToModel($model)
    {
        foreach ($this->automaticStyles as $style) {
            if(!request($style)) continue;

            $model->content->replaceProperty($style, request($style));
        }
    }
}
