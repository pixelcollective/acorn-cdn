<?php

namespace TinyPixel\Acorn\CDN\Interfaces;

use Illuminate\Cache\Repository;

interface CacheableService
{
    public function __construct(\Illuminate\Cache\Repository $cache);

    public function cacheUrl(string $url);

    public function cacheMarkup(string $html);
}
