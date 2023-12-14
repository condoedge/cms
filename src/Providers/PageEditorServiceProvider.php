<?php

namespace Anonimatrix\PageEditor\Providers;

use Anonimatrix\PageEditor\Features\EditorVariablesService;
use Anonimatrix\PageEditor\Features\TeamsService;
use Anonimatrix\PageEditor\Features\FeaturesService;
use Anonimatrix\PageEditor\Services\PageEditorService;
use Anonimatrix\PageEditor\Services\PageItemService;
use Anonimatrix\PageEditor\Services\PageStyleService;
use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Illuminate\Support\ServiceProvider;

class PageEditorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadConfig();

        $this->loadCommands();

        $this->loadPublishes();

        $this->loadTranslations();

        $this->loadRoutes();

        PageModel::creating(function ($page) {
            $page->beforeSave();
        });
    }

    public function register(): void
    {
        $this->app->singleton('page-editor', function () {
            return new PageEditorService();
        });

        $this->app->singleton('page-item', function () {
            return new PageItemService();
        });

        $this->app->singleton('page-style-service', function () {
            return new PageStyleService(config("page-editor.automapping_styles"));
        });

        $this->app->singleton('page-editor-features', function () {
            $featureService = new FeaturesService();

            collect(config('page-editor.features', []))
                ->each(function ($val, $feature) use ($featureService) {
                    if($val) $featureService->addFeature($feature);
                });

            return $featureService;
        });

        $this->registerFeatures();

        $this->registerModels();
    }

    protected function registerModels()
    {
        $this->app->bind('page-model', function () {
            $class = config('page-editor.models.page');

            $this->checkRegisterModel($class, \Anonimatrix\PageEditor\Models\Page::class);

            return new $class();
        });

        $this->app->bind('page-item-model', function () {
            $class = config('page-editor.models.page_item');

            $this->checkRegisterModel($class, \Anonimatrix\PageEditor\Models\PageItem::class);

            return new $class();
        });

        $this->app->bind('page-item-style-model', function () {
            $class = config('page-editor.models.page_item_style');

            $this->checkRegisterModel($class, \Anonimatrix\PageEditor\Models\PageItemStyle::class);

            return new $class();
        });
    }

    protected function checkRegisterModel($class, $expected)
    {
        if (!is_subclass_of($class, $expected) && $class !== $expected) {
            throw new \Exception('Page model must extend ' . $expected);
        }
    }

    protected function registerFeatures()
    {
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

    protected function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands here
            ]);
        }
    }

    protected function loadConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/page-editor.php', 'page-editor');
    }

    protected function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/page-editor.php');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang/*', 'page-editor');
    }

    protected function loadPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/page-editor.php' => config_path('page-editor.php'),
            __DIR__ . '/../../database/migrations/' => database_path('migrations/page-editor'),
            __DIR__ . '/../Models' => app_path('Models/PageEditor'),
            __DIR__ . '/../../resources/lang' => resource_path('lang/vendor/page-editor'),
        ], 'page-editor');
    }
}
