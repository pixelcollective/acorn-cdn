<?php

namespace TinyPixel\Acorn\CDN\Concerns;

use Illuminate\Cache\Repository;

trait Cache
{
    /**
     * Cache
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * Cache expiry in seconds
     *
     * @var int
     */
    protected $cacheExpiry;

    /**
     * Constructor
     *
     * @param  \Illuminate\Cache\Repository $cache
     * @return void
     */
    public function useCache(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Provides cached output of isolated URL replacement
     *
     * @param  string $url
     * @return string
     */
    public function cacheUrl(string $url)
    {
        return $this->cache->remember("cdn.{$url}", $this->cacheExpiry, function () use ($url) {
            return $this->rewriteUrl($url);
        });
    }

    /**
     * Caches output of markup processing
     *
     * @param  string $html
     * @return string
     */
    public function cacheMarkup($html)
    {
        return $this->cache->remember("cdn.{$html}", $this->cacheExpiry, function () use ($html) {
            return $this->rewriteMarkup($html);
        });
    }
}
