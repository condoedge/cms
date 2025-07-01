<?php

namespace Anonimatrix\PageEditor\Features;

class EditorVariablesService
{
    protected $variables = [];

    /**
     * Set variables for a specific section.
     *
     * @param callable $callback The callback function to set the variables.
     * @param string $section The section to set the variables for. Default is 'default'.
     * @return void
     */
    public function setVariables(callable $callback, $section = 'default')
    {
        $this->variables[$section] = $callback($this);
    }

    /**
     * Create a link with specific attributes.
     *
     * @param string $label The label for the link.
     * @param string $type The type of the link.
     * @param string|null $class The CSS class for the link. Default is null.
     * @return \Anonimatrix\PageEditor\Components\Link
     */
    public function link($label, $type, $class = null)
	{
		return _Link($label)->attr(['data-type' => $type])
            ->class($class . 'hover:bg-blue-50 text-black bg-white rounded-lg px-3 py-2')
            ->emitRoot('insertVariable', ['type' => $type, 'label' => __($label)]);
	}

    /**
     * Get the variables for a specific section.
     *
     * @param string $section The section to get the variables for. Default is 'default'.
     * @return mixed
     */
    public function getVariables($section = 'default')
    {
        return $this->variables[$section] ?? [];
    }
}