<?php

namespace Anonimatrix\PageEditor\Providers;

use Anonimatrix\PageEditor\PageEditor;
use Illuminate\Support\ServiceProvider;

class PageEditorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadCommands();

        $this->loadPublishes();
    }

    public function register(): void
    {
        $this->app->singleton('page-editor', function () {
            return new PageEditor();
        });
    }

    private function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands here
            ]);
        }
    }

    private function loadPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/page-editor.php' => config_path('page-editor.php'),
            __DIR__ . '/../../database/migrations/' => database_path('migrations/page-editor'),
            __DIR__.'/../Models' => app_path(),
        ], 'page-editor');
    }
}
