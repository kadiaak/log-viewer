<?php

namespace Kadiaak\LogViewer;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kadiaak\LogViewer\Http\Middleware\Authorize;

class LogViewerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/log-viewer.php', 'log-viewer');

        $this->app->singleton(LogViewer::class, fn () => new LogViewer());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'log-viewer');

        $this->registerRoutes();
        $this->registerPublishing();
    }

    protected function registerRoutes(): void
    {
        if (! config('log-viewer.enabled', true)) {
            return;
        }

        Route::group([
            'domain' => config('log-viewer.route.domain'),
            'prefix' => config('log-viewer.route.prefix', 'log-viewer'),
            'middleware' => array_merge(
                (array) config('log-viewer.route.middleware', ['web']),
                [Authorize::class]
            ),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/log-viewer.php' => config_path('log-viewer.php'),
        ], 'log-viewer-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/log-viewer'),
        ], 'log-viewer-views');
    }
}
