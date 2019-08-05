<?php

namespace TinyPixel\Acorn\CDN\Providers;

use function Roots\config;
use function Roots\config_path;
use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
use Illuminate\Support\Collection;
use TinyPixel\Acorn\CDN\UrlRewriter;

/**
 * AcornCDN Service Provider
 *
 * @see Roots\Acorn\ServiceProvider https://github.com/roots/acorn/blob/master/src/Acorn/ServiceProvider.php
 *
 * @author  Kelly Mears <kelly@tinypixel.dev>
 * @license MIT
 * @since   1.0.0
 */
class CDNServiceProvider extends ServiceProvider
{
    /**
     * Registers and configures the service
     * with the Acorn IOC
     *
     * @return void
     */
    public function register()
    {
        if ($this->isBootable()) {
            $this->mergeConfigFrom(config_path('cdn'), 'cdn');

            $this->config = Collection::make(config('cdn'));

            $this->app->singleton('cdn', function ($app) {
                $cdn = new UrlRewriter($app->make('cache')->store('file'));
                $cdn->init($this->config);
            });
        }
    }

    /**
     * Instantiates the CDN singleton and registers
     * publishable items with the Acorn CLI.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/cdn.php' => config_path('cdn.php'),
        ], 'AcornCDN');

        if ($this->isBootable()) {
            $this->app->make('cdn');
        }
    }

    /**
     * Boot if configuration set and in specified environment
     *
     * @return void
     */
    public function isBootable()
    {
        return config('cdn.env') == config('app.env');
    }
}
