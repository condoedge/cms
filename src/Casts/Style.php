<?php

namespace Anonimatrix\PageEditor\Casts;

class Style {
    // Missing filters. If is required, add them.
    const FILTERS = [
        'px',
        'rem',
        'vh',
        'vw',
        '%',
        '!important',
    ];

    /**
     * @var string The raw style string.
     */
    protected string $rawStyle;

    public function __construct(string|null $rawStyle) {
        $this->rawStyle = $rawStyle ?? '';
    }

    public function __toString() {
        return $this->rawStyle;
    }

    public function __get($name) {
        $name = str_replace('_', '-', $name);

        if(str_ends_with($name, '-raw')) {
            $name = str_replace('-raw', '', $name);

            return $this->getRawProperty($name);
        }

        return $this->getProperty($name);
    }

    /* GETTERS */

    /**
     * Get the value of all styles as a collection.
     * @return \Illuminate\Support\Collection<array-key, string>
     */
    public function getProperties()
    {
        $styles = collect(explode(';', $this->rawStyle))->filter()->mapWithKeys(function ($style) {
            $style = explode(':', $style);

            return [trim($style[0]) => trim($style[1])];
        });

        return $styles;
    }

    /**
     * Get the value of a specific style.
     * @param string $property The name of the style.
     * @param array<string> $filter The filters to remove from the style value.
     * @return string|null
     */
    public function getProperty($property, $filter = [])
    {
        $styles = $this->getProperties();

        $style = $styles[$property] ?? '';

        if($style && count($filter)) {
            $style = str_replace($filter, '', $style);
        }

        return trim($style ?: '') ?? null;
    }

    /**
     * Get the value of a specific style with default filters.
     * @param string $property The name of the style.
     * @return string|null
     */
    public function getRawProperty($property)
    {
        return $this->getProperty($property, self::FILTERS);
    }


    /* REMOVES */

    /**
     * Remove a specific style.
     * @param string $propertyToRemove The name of the style to remove.
     * @return void
     */
    public function removeProperty($propertyToRemove)
    {
        $this->rawStyle = preg_replace("/(^|;)\s*{$propertyToRemove}:(.*?);/", '$1', $this->rawStyle);
    }

    /**
     * Remove a list of styles.
     * @param array<string> $propertiesToRemove The list of styles to remove.
     * @return void
     */
    public function removeProperties(array $propertiesToRemove)
    {
        foreach ($propertiesToRemove as $propertyToRemove) {
            $this->removeProperty($propertyToRemove);
        }
    }


    /** SETTERS */

    /**
     * Replace or set a list of styles with new values.
     * @param array<string, string|null> $propertiesToReplace The list of styles to replace with values.
     * @param bool $notReplaceIfNull If true, the style won't be replaced if the new value is null.
     * @return void
     */
    public function replaceProperties(array $propertiesToReplace, $notReplaceIfNull = true)
    {
        foreach ($propertiesToReplace as $propertyToReplace => $newValue) {
            if($notReplaceIfNull && $newValue == null) continue;

            $this->replaceProperty($propertyToReplace, $newValue);
        }
    }

    /**
     * Replace or set a specific style with a new value. If the value is null, the style will be removed.
     * @param string $propertyToReplace The name of the style to replace.
     * @param string|null $newValue The new value of the style.
     * @return void
     */
    public function replaceProperty($propertyToReplace, $newValue)
    {
        $this->removeProperty($propertyToReplace);

        $this->rawStyle .= "{$propertyToReplace}: {$newValue};";
    }
}