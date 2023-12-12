<?php

return [
    'models' => [
        'page' => \Anonimatrix\PageEditor\Models\Page::class,
        'page_item' => \Anonimatrix\PageEditor\Models\PageItem::class,
        'page_item_style' => \Anonimatrix\PageEditor\Models\PageItemStyle::class,
    ],

    'components' => [
        'page-item-styles-form' => \Anonimatrix\PageEditor\Components\Cms\StylePageItemForm::class,
        'page-content-form' => \Anonimatrix\PageEditor\Components\Cms\PageContentForm::class,
        'page-design-form' => \Anonimatrix\PageEditor\Components\Cms\PageDesignForm::class,
        'page-item-form' => \Anonimatrix\PageEditor\Components\Cms\PageItemForm::class,
        'page-preview' => \Anonimatrix\PageEditor\Components\Cms\PagePreview::class,
    ],

    'types' => [
        \Anonimatrix\PageEditor\Items\ItemTypes\H1Item::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\H2Item::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\ImgItem::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\VideoItem::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\CKItem::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\ButtonItem::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\KompoItem::class,
        \Anonimatrix\PageEditor\Items\ItemTypes\ElementType1Item::class,
    ],
    
    'hidden_types' => [ // Won't be displayed, but it will work in groups
        \Anonimatrix\PageEditor\Items\ItemTypes\H2Item::class,
    ],

    'features' => [
        'teams' => false,
        'editor_variables' => false,
    ],

    'teams' => [
        // 'model' => \App\Models\Team::class,
    ],

    'default_font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",

    'automapping_styles' => [
        'padding-top' => 'px',
        'padding-bottom' => 'px',
        'padding-left' => 'px',
        'padding-right' => 'px',

        'font-size' => 'px',
        'color' => '',
        'background-color' => '',
        'text-align' => '',

        'link-color' => '',
    ],
];