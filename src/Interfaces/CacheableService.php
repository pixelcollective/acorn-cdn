<?php

namespace TinyPixel\Acorn\CDN\Interfaces;

use Roots\Acorn\Application;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

interface CacheableService
{
    public function init(Repository $cache, Collection $config);

    public function useCache(Repository $cache);

    public function cacheUrl(string $url);

    public function cacheMarkup(string $html);
}
