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
        foreach ($this->automaticStyles as $styleName => $styleSuffix) {
            if(!request($styleName)) continue;

            $model->content->replaceProperty($styleName, request($styleName) . $styleSuffix);
        }
    }
}
