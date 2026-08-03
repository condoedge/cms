<?php

namespace Anonimatrix\PageEditor\Services;

use Illuminate\Support\Facades\Route;

class PageEditorService
{
    /**
     * Get the available types from the configuration.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableTypes($prefixGroup = "")
    {
        return collect(config('page-editor.types'))->filter(function ($item, $prefixGroup) {
            return !in_array($item, config('page-editor.hidden_types')) && (!$item::SPECIFIC_GROUP || $item::SPECIFIC_GROUP == $prefixGroup);
        });
    }

    /**
     * Get the available types formatted for the select options.
     *
     * @return array
     */
    public function getOptionsTypes($prefixGroup = "")
    {
        return collect($this->getAvailableTypes($prefixGroup))->mapWithKeys(function ($item) {
            return [$item::ITEM_NAME => __($item::ITEM_TITLE)];
        })->toArray();
    }

    /**
     * Build the block-library category structure: each category lists its full type classes,
     * with any uncategorized (and not library-hidden) type flowing into the implicit "other"
     * bucket. Categories are driven entirely by `page-editor.block_categories` config.
     *
     * Returned shape: [
     *   'category-key' => ['label' => '...', 'items' => Collection<TypeClass>],
     *   ...
     *   'other' => ['label' => '...', 'items' => Collection<TypeClass>],
     * ]
     */
    public function getCategorizedTypes($prefixGroup = "")
    {
        $categories = config('page-editor.block_categories', []);
        $libraryHidden = config('page-editor.library_hidden_types', []);

        $bucketed = collect($categories)->map(fn ($cat) => [
            'label' => $cat['label'],
            'items' => collect(),
        ])->toArray();

        $otherItems = collect();

        foreach ($this->getAvailableTypes($prefixGroup) as $typeClass) {
            $itemName = $typeClass::ITEM_NAME;

            if (in_array($itemName, $libraryHidden, true)) continue;

            $placed = false;
            foreach ($categories as $catKey => $cat) {
                if (in_array($itemName, $cat['types'] ?? [], true)) {
                    $bucketed[$catKey]['items']->push($typeClass);
                    $placed = true;
                    break;
                }
            }

            if (!$placed) {
                $otherItems->push($typeClass);
            }
        }

        if ($otherItems->isNotEmpty()) {
            $bucketed['other'] = [
                'label' => config('page-editor.library_other_label', 'cms::cms.category-other'),
                'items' => $otherItems,
            ];
        }

        return $bucketed;
    }

    /**
     * Set the routes for the package.
     *
     * @param string $route
     * @return void
     */
    public function setRoutes($route = 'crm/page/{page_id}/preview')
    {
        // We need to remove layout
        Route::get($route, \Anonimatrix\PageEditor\Components\Cms\PagePreview::class)->name('page.preview');
    }

    /**
     * Get a visual component. (abstracted)
     *
     * @param string $name
     * @param mixed $default
     * @param array $args
     * @return mixed
     */
    protected function getComponent($name, $default, $args = [])
    {
        $prefixComponent = (count($args) > 0 && !is_numeric($args[0]) && !is_array($args[0])) ? ($args[0] . '.') : '';

        $otherArgs = (count($args) > 0 && $prefixComponent) ? array_slice($args, 1) : $args;

        // Knowing if it's form and first arg is the model id or the array of props, we need to merge the prefix_group into the props array
        $indexWithProps = is_numeric($otherArgs[0] ?? null) ? 1 : 0;
        $otherArgs[$indexWithProps] = array_merge($otherArgs[$indexWithProps] ?? [], ['prefix_group' => trim($prefixComponent, '.')]);

        return new (config('page-editor.components.' . $prefixComponent . $name, $default))(...$otherArgs);
    }

    /**
     * Get the page details form component.
     */
    public function getPageInfoFormComponent(...$args)
    {
        return $this->getComponent('page-info-form', \Anonimatrix\PageEditor\Components\Cms\PageInfoForm::class, $args);
    }

    /**
     * Get the page item form component.
     */
    public function getPageItemFormComponent(...$args)
    {
        return $this->getComponent('page-item-form', \Anonimatrix\PageEditor\Components\Cms\PageItemForm::class, $args);
    }

    /**
     * Get the page style form component.
     */
    public function getPageStyleFormComponent(...$args)
    {
        return $this->getComponent('page-style-form', \Anonimatrix\PageEditor\Components\Cms\PageStylingForm::class, $args);
    }

    /**
     * Get the page preview component.
     */
    public function getPagePreviewComponent(...$args)
    {
        return $this->getComponent('page-preview', \Anonimatrix\PageEditor\Components\Cms\PagePreview::class, $args);
    }

    /**
     * Get the complete page form with details and design forms.
     */
    public function getPageFormComponent(...$args)
    {
        return $this->getComponent('page-content-form', \Anonimatrix\PageEditor\Components\Cms\PageContentForm::class, $args);
    }

    /**
     * Get the page design (page-editor) form component.
     */
    public function getPageDesignFormComponent(...$args)
    {
        return $this->getComponent('page-design-form', \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class, $args);
    }

    /**
     * Get the page item styles form component.
     */
    public function getItemStylesFormComponent(...$args)
    {
        return $this->getComponent('page-item-styles-form', \Anonimatrix\PageEditor\Components\Cms\StylePageItemForm::class, $args);
    }

    /**
     * Get the page editor layout component (3-panel block editor).
     */
    public function getPageEditorComponent(...$args)
    {
        return $this->getComponent('page-editor-layout', \Anonimatrix\PageEditor\Components\Cms\PageEditorLayout::class, $args);
    }

    /**
     * Get the block library panel component.
     */
    public function getBlockLibraryComponent(...$args)
    {
        return $this->getComponent('block-library-panel', \Anonimatrix\PageEditor\Components\Cms\BlockLibraryPanel::class, $args);
    }

    /**
     * Get the editor top bar component.
     */
    public function getEditorTopBarComponent(...$args)
    {
        return $this->getComponent('editor-top-bar', \Anonimatrix\PageEditor\Components\Cms\EditorTopBar::class, $args);
    }
}