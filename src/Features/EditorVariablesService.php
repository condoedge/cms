<?php

namespace Anonimatrix\PageEditor\Features;

class EditorVariablesService
{
    protected $variables = [];

    public function setVariables(callable $callback)
    {
        $this->variables = $callback($this);
    }

    public function link($label, $type, $class = null)
	{
		return _Link($label)->attr(['data-type' => $type])
            ->class($class . 'hover:bg-blue-50 text-black bg-white rounded-lg px-3 py-2')
            ->emitRoot('insertVariable', ['type' => $type, 'label' => __($label)]);
	}

    public function getVariables()
    {
        return $this->variables;
    }
}