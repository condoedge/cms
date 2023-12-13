<?php

namespace Anonimatrix\PageEditor\Services;

class PageStyleService
{
    protected $automaticStyles;

    public function __construct($autoStyles = [])
    {
        $this->automaticStyles = $autoStyles;
    }

    public function setStylesToModel($model, $otherStyles = [])
    {
        $stylesToMap = array_merge($this->automaticStyles, $otherStyles);

        foreach ($stylesToMap as $styleName => $styleSuffix) {
            if(!request($styleName)) {
                $model->content->removeProperty($styleName);
                continue;
            }

            $model->content->replaceProperty($styleName, request($styleName) . $styleSuffix);
        }
    }
}
