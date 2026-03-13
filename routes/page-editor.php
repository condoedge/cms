<?php

use Anonimatrix\PageEditor\Http\ImageMethods;
use Illuminate\Support\Facades\Route;

// PageEditor::setRoutes(); // This will be called by user

Route::post('page-editor/get-image-size', [ImageMethods::class, 'getDefaultMaxWidth'])->name('page-editor.get-image-size');
Route::get('page-editor/get-full-view', [ImageMethods::class, 'getFullView'])->name('page-editor.get-full-view');

Route::middleware(['web', 'auth'])->get('page-editor/{page_id}/add-block/{block_type}', function ($page_id, $block_type) {
    $pageModel = config('page-editor.models.page', \Anonimatrix\PageEditor\Models\Page::class);
    $pageItemModel = config('page-editor.models.page_item', \Anonimatrix\PageEditor\Models\PageItem::class);
    $page = $pageModel::findOrFail($page_id);

    $item = $pageItemModel::make();
    $item->page_id = $page_id;
    $item->block_type = $block_type;
    $item->order = $page->pageItems()->count();
    $item->save(['skip_validation' => true]);

    return response()->json(['id' => $item->id]);
})->name('page-editor.add-block');

Route::middleware(['web', 'auth'])->get('page-editor/{page_id}/copy-block-form', function ($page_id) {
    $panel = new \Anonimatrix\PageEditor\Components\Cms\BlockLibraryPanel(null, ['page_id' => $page_id]);
    return $panel->getCopyBlockForm($page_id);
})->name('page-editor.copy-block-form');

Route::get('page-editor/{page_id}/export-html', function ($page_id) {
    $pageModel = config('page-editor.models.page', \Anonimatrix\PageEditor\Models\Page::class);
    $page = $pageModel::findOrFail($page_id);

    $htmlContent = $page->getHtmlContent();
    $bgColor = $page->getExteriorBackgroundColor();
    $contentBg = $page->getContentBackgroundColor();
    $textColor = $page->getTextColor();
    $linkColor = $page->getLinkColor();
    $fontSize = $page->getFontSize();
    $maxWidth = $page->getContentMaxWidth();
    $fontFamily = $page->getFontFamily();

    $sendTestForm = new \Anonimatrix\PageEditor\Components\Cms\SendTestEmailForm(null, ['page_id' => $page_id]);
    $fullHtml = $sendTestForm->buildEmailHtml($htmlContent, $bgColor, $contentBg, $textColor, $linkColor, $fontSize, $maxWidth, $fontFamily);

    $filename = str_replace(' ', '-', strtolower($page->title ?: 'email')) . '.html';

    return response($fullHtml)
        ->header('Content-Type', 'text/html')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->name('page-editor.export-html');

Route::middleware(['web', 'auth'])->get('page-editor/{page_id}/copy-block-items', function ($page_id) {
    $pageModel = config('page-editor.models.page', \Anonimatrix\PageEditor\Models\Page::class);
    $pageItemModel = config('page-editor.models.page_item', \Anonimatrix\PageEditor\Models\PageItem::class);

    $sourcePageId = request('select_newsletter');
    if (!$sourcePageId) return _Html('');

    $page = $pageModel::findOrFail($sourcePageId);
    $items = $page->orderedMainPageItems()->get();

    $options = $items->mapWithKeys(function ($item) {
        $type = $item->getPageItemType();
        $typeName = $type ? __($type::ITEM_TITLE) : '';
        $zoneName = $item->name_pi ?: $typeName;
        $label = $zoneName . ($item->name_pi ? ' (' . $typeName . ')' : '');
        return [$item->id => $label];
    });

    return _Rows(
        _Select('cms::cms.select-block')->name('select_block', false)
            ->options($options->toArray()),
        _Button('cms::cms.copy-this-block')->icon('duplicate')
            ->post('page-editor.copy-block-to-page', ['page_id' => $page_id])
            ->onSuccess(fn($e) => $e->run('() => { if (window.vlEmailEditor) vlEmailEditor.refreshPreview() }'))
            ->class('mt-3 w-full'),
    );
})->name('page-editor.copy-block-items');

Route::middleware(['web', 'auth'])->post('page-editor/{page_id}/copy-block-to-page', function ($page_id) {
    $pageModel = config('page-editor.models.page', \Anonimatrix\PageEditor\Models\Page::class);
    $pageItemModel = config('page-editor.models.page_item', \Anonimatrix\PageEditor\Models\PageItem::class);

    $sourceItem = $pageItemModel::findOrFail(request('select_block'));
    $page = $pageModel::findOrFail($page_id);

    $newItem = $sourceItem->replicate();
    $newItem->page_id = $page_id;
    $newItem->order = $page->pageItems()->count();
    $newItem->page_item_id = null;
    $newItem->group_page_item_id = null;
    $newItem->save(['skip_validation' => true]);

    if ($sourceItem->styles) {
        $newStyles = $sourceItem->styles->replicate();
        $newItem->styles()->save($newStyles);
    }

    $sourceItem->groupPageItems()->each(function ($groupItem) use ($newItem) {
        $newGroupItem = $groupItem->replicate();
        $newGroupItem->group_page_item_id = $newItem->id;
        $newGroupItem->page_id = $newItem->page_id;
        $newGroupItem->save(['skip_validation' => true]);

        if ($groupItem->styles) {
            $newGroupStyles = $groupItem->styles->replicate();
            $newGroupItem->styles()->save($newGroupStyles);
        }
    });

    return response()->json(['id' => $newItem->id]);
})->name('page-editor.copy-block-to-page');