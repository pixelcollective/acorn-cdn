<?php

namespace TinyPixel\Acorn\CDN\Providers;

use TinyPixel\Acorn\CDN\UrlRewriter;
use Illuminate\Support\Collection;
use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
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
        $this->handleConfiguration();

        if (! $this->cdnConfig->isEmpty()) {
            $this->app->singleton('cdn', function () {
                (new UrlRewriter($this->app))->init($this->cdnConfig);
            });
        }
    }

    /**
     * Boots services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/cdn.php' => config_path('cdn.php'),
        ]);

        $this->app->make('cdn');
    }

    /**
     * Configure services.
     *
     * @return void
     */
    public function handleConfiguration()
    {
        if (file_exists(config_path('cdn'))) {
            $this->mergeConfigFrom(config_path('cdn'), 'cdn');
        }

        $this->cdnConfig = Collection::make(config('cdn'));
    }
}