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

        $variables = Features::hasFeature('editor_variables')
            ? Variables::getVariables()
            : [];

		$this->config([
			'variables' => $variables,
		]);
	}

    public function withoutHeight()
    {
        return $this->config([
            'withoutHeight' => true,
        ]);
    }
}
