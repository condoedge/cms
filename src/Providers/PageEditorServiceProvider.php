<?php

namespace Anonimatrix\PageEditor\Providers;

use Anonimatrix\PageEditor\Features\EditorVariablesService;
use Anonimatrix\PageEditor\Features\TeamsService;
use Anonimatrix\PageEditor\Features\FeaturesService;
use Anonimatrix\PageEditor\PageEditorService;
use Anonimatrix\PageEditor\PageItemService;
use Anonimatrix\PageEditor\Support\Facades\Features;
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
            return new PageEditorService();
        });

        $this->app->singleton('page-item', function () {
            return new PageItemService();
        });

        $this->app->singleton('page-editor-features', function () {
            $featureService = new FeaturesService();

            collect(config('page-editor.features', []))
                ->each(function ($feature) use ($featureService) {
                    $featureService->addFeature($feature);
                });

            return $featureService;
        });

        if (Features::hasFeature('teams')) {
            $this->app->singleton('page-editor-teams', function () {
                $teamsService = new TeamsService();

                $teamsService->setTeamClass();

                return $teamsService;
            });
        }

        if (Features::hasFeature('editor_variables')) {
            $this->app->singleton('page-editor-variables', function () {
                return new EditorVariablesService();
            });
        }
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
