<?php

namespace Anonimatrix\PageEditor\Components;

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Features\Variables;
use Kompo\TranslatableEditor;

class CKEditorPageItem extends TranslatableEditor
{
    public $vueComponent = 'CKEditorTemplate';

	public function initialize($label)
	{
        parent::initialize($label);

		$this->class('vlTranslatableEditor relative comms-editor');

        $this->setVariablesSection();
	}

    public function withoutHeight()
    {
        return $this->config([
            'withoutHeight' => true,
        ]);
    }

    public function setVariablesSection($section = 'default')
    {
        $variables = Features::hasFeature('editor_variables')
            ? Variables::getVariables($section)
            : [];

        $this->config([
            'variables' => $variables,
        ]);
    }
}
