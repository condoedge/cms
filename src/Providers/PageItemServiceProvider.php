<?php

namespace Anonimatrix\PageEditor\Providers;

class PageItemServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('page-item-types', function () {
            return collect(config('page-editor.types'));
        });
    }
}