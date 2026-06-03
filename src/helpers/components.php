<?php

if (!function_exists('_CKEditorPageItem')) {
    function _CKEditorPageItem()
    {
        return \Anonimatrix\PageEditor\Components\CKEditorPageItem::form(...func_get_args());
    }
}

if (!function_exists('_PageEditorSection')) {
    /**
     * Card-style collapsible used uniformly across the page editor drawer:
     * a rounded white card with a gray header bar (icon + bold title +
     * chevron) and a white body separated by a top border. Same language as
     * the block-cards in the editor's left panel.
     */
    function _PageEditorSection(string $title, $body, ?string $icon = null)
    {
        $titleEl = _Flex(
            !$icon ? null : _Html()->icon(_Sax($icon, 18))->class('text-level3 shrink-0'),
            _Html($title)->class('text-sm font-semibold text-gray-900 flex-1'),
        )->class('flex-1 items-center gap-2 pl-4 py-3 cursor-pointer bg-white hover:bg-gray-50');

        return _Collapsible(
            _Rows($body)->class('px-4 py-3 bg-white border-t border-gray-200'),
        )
            ->titleLabel($titleEl)
            ->withIcon('chevron-down', 'text-gray-700 text-xl shrink-0')
            ->class('vlPageEditorCollapsible border border-gray-200 rounded-xl overflow-hidden mb-3 bg-white');
    }
}
