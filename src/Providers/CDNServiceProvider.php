<?php

namespace TinyPixel\Acorn\CDN\Providers;

use function Roots\config;
use function Roots\config_path;
use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
use TinyPixel\Acorn\CDN\UrlRewriter;
use Illuminate\Support\Collection;

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
            $this->mergeConfigFrom($this->app->configPath('cdn.php'), 'cdn');

            $this->app->singleton('cdn', function ($app) {
                return new UrlRewriter($app);
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
        $publishableConfig = __DIR__ . '/../../config/cdn.php';

        $this->publishes([
            $publishableConfig => $this->app->configPath('cdn.php'),
        ], 'AcornCDN');

        if ($this->isBootable()) {
            $this->app->make('cdn')->init(
                $this->app->make('cache')->store('file'),
                Collection::make($this->app['config']->get('cdn'))
            );
        }
    }

    /**
     * Boot if configuration set and in specified environment
     *
     * @return void
     */
    public function isBootable()
    {
        return ! in_array(
            $this->app['config']->get('app.env'),
            $this->app['config']->get('cdn.env')
        );
    }
}
