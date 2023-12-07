<?php

namespace Anonimatrix\PageEditor;

class FeaturesService
{
    protected $features = [];

    public function addFeature(string $feature)
    {
        $this->features[] = $feature;
    }

    public function hasFeature(string $feature)
    {
        return in_array($feature, $this->features);
    }
}