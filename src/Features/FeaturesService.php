<?php

namespace Anonimatrix\PageEditor\Features;

class FeaturesService
{
    protected $features = [];

    /**
     * Add a feature to the list of features.
     * 
     * @param string $feature
     * @return void
     */
    public function addFeature(string $feature)
    {
        $this->features[] = $feature;
    }

    /**
     * Determine if the given feature exists.
     * 
     * @param string $feature
     * @return bool
     */
    public function hasFeature(string $feature)
    {
        return in_array($feature, $this->features);
    }
}