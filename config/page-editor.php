<?php

return [
    'models' => [
        'page' => \Anonimatrix\PageEditor\Models\Page::class,
        'page_item' => \Anonimatrix\PageEditor\Models\PageItem::class,
        'page_item_style' => \Anonimatrix\PageEditor\Models\PageItemStyle::class,
    ],

    'components' => [
        'page-item-styles-form' => \Anonimatrix\PageEditor\Components\StylePageItemForm::class,
        'page-content-form' => \Anonimatrix\PageEditor\Components\PageContentForm::class,
        'page-design-form' => \Anonimatrix\PageEditor\Components\PageDesignForm::class,
    ],

    'types' => [
        \Anonimatrix\PageEditor\Cms\ItemTypes\H1Item::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\H2Item::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\ImgItem::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\VideoItem::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\CKItem::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\ButtonItem::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\KompoItem::class,
        \Anonimatrix\PageEditor\Cms\ItemTypes\ElementType1Item::class,
    ],
    'hidden_types' => [ // Won't be displayed, but it will work in groups
        \Anonimatrix\PageEditor\Cms\ItemTypes\H2Item::class,
    ],
    'features' => [
        'teams' => false,
        'editor_variables' => false,
    ],
    'teams' => [
        // 'model' => \App\Models\Team::class,
    ],
    'default_font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
];