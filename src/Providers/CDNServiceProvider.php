<?php

namespace TinyPixel\Acorn\CDN\Providers;

use TinyPixel\Acorn\CDN\Rewriter;
use Illuminate\Support\Collection;
use Roots\Acorn\Application;
use function Roots\config_path;
use function Roots\config;

class CDNServiceProvider extends ServiceProvider
{
    /**
     * Register with container.
     *
     * @return void
     */
    public function register()
    {
        $this->configureServices();

        if (! $this->cdnConfig->empty()) {
            $this->app->singleton('cdn', function () {
                (new Rewriter($this->cdnConfig))->init();
            });
        }
    }

    /**
     * Boots services.
     */
    public function boot()
    {
        $this->app->make('cdn');
    }

    /**
     * Configure services.
     */
    public function configureServices()
    {
        if (file_exists(config_path('cdn'))) {
            $this->mergeConfigFrom(config_path('cdn'), 'cdn');
        }

        $this->cdnConfig = Collection::make(config('cdn'));
    }
}