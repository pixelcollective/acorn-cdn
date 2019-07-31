<?php

namespace TinyPixel\Acorn\CDN;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Roots\Acorn\Application;
use function Roots\add_filters;

/**
 * Url Rewriter
 *
 * @author  Kelly Mears <kelly@tinypixel.dev>
 * @license MIT
 * @since   1.0.0
 *
 * @package    WordPress
 * @subpackage AcornCDN
 */
class UrlRewriter
{
    /**
     * CDN asset base
     * @var string
     */
    protected $cdnBase;

    /**
     * Local asset base
     * @var string
     */
    protected $localBase;

    /**
     * Cache expiry in seconds
     * @var int
     */
    protected $cacheExpiry;

    /**
     * Include directories
     * @var Illuminate\Support\Collection
     */
    protected $includeDirs;

    /**
     * Exclude directories
     * @var Illuminate\Support\Collection
     */
    protected $excludeTypes;

    /**
     * Asset Filters
     * @var array
     */
    protected $assetFilters = [
        'theme_root_uri',
        'plugins_url',
        'script_loader_src',
        'style_loader_src',
    ];

    /**
     * Content filters
     * @var array
     */
    protected $markupFilters = [
        'the_content',
        'the_excerpt',
    ];

    /**
     * Constructor
     *
     * @return TinyPixel\Acorn\CDN\AssetRewriter $this
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * Initialize
     *
     * @param  Illuminate\Support\Collection $config
     * @return void
     */
    public function init(Collection $config)
    {
        $this->config($config);
        $this->setHooks();
    }

    /**
     * Configure CDN
     *
     * @param  Illuminate\Support\Collection $config
     * @return void
     */
    protected function config(Collection $config)
    {
        $this->localBase   = $config->get('local_url');
        $this->cdnBase     = $config->get('cdn_url');
        $this->cacheExpiry = $config->get('cache_expiry');

        $this->includeDirs = Collection::make(
            $config->get('include_directories')
        );

        $this->excludeTypes = Collection::make(
            $config->get('exclude_types')
        );
    }

    /**
     * Set WordPress actions & filters
     *
     * @return void
     */
    protected function setHooks()
    {
        add_filters($this->assetFilters,  [$this, 'rewriteAsset'], 99, 1);
        add_filters($this->markupFilters, [$this, 'handleMarkup'], 99, 1);
    }

    /**
     * Handle arbitrary HTML strings
     *
     * @param  string $html
     * @return string
     */
    public function handleMarkup($html)
    {
        return Cache::store('file')->remember("cdn{$html}", $this->cacheExpiry, function () use ($html) {
            return preg_replace_callback($this->expression(), [
                $this, 'rewriteAsset',
            ], $html);
        });
    }

    /**
     * Replace URLs with CDN
     *
     * @param  string $url
     * @return string
     */
    public function rewriteAsset(string $url)
    {
        return Cache::store('file')->remember("cdn.{$url}", $this->cacheExpiry, function () use ($url) {
            if ($this->checkIfTypeExcluded($url) || $this->isPreview()) {
                return $url;
            }

            return str_replace($this->localBase, $this->cdnBase, $url);
        });
    }

    /**
     * Base a unique cache key
     *
     * @param  string $html
     * @return string
     */
    protected function baseId($val)
    {
        return intval($val, 32);
    }

    /**
     * Prevent rewrites if current view is a post preview
     *
     * @return boolean
     */
    protected function isPreview()
    {
        if (is_admin_bar_showing()) {
            return array_key_exists('preview', $_GET) && $_GET['preview'] == 'true';
        }

        return false;
    }

    /**
     * Exclude assets that should not be rewritten
     *
     * @param  string  $asset  current asset
     * @return boolean true if need to be excluded
     */
    protected function checkIfTypeExcluded($asset, $doExclude = false) {
        $this->excludeTypes->each(function ($exclude) use ($doExclude, $asset) {
            $doExclude = !! $exclude && stristr($asset, ".{$exclude}") != false ?? true;
        });

        return $doExclude;
    }

    /**
     * Construct regular expression for URL replacement
     *
     * @return string
     */
    protected function expression()
    {
        $template = '#(?<=[(\"\'])%1$s/(?:((?:%2$s)[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

        $dirs = $this->formatTargetDirsForRegex();
        $url  = $this->formatBaseUrlForRegex();

        return sprintf($template, $url, $dirs);
    }

    /**
     * Format URL for regular expression use
     *
     * @return string
     */
    protected function formatBaseUrlForRegex()
    {
        $baseUrl = $_SERVER['HTTPS']
            ? '(https?:|)' . $this->relativizeUrl(quotemeta($this->localBase))
            : '(http:|)' . $this->relativizeUrl(quotemeta($this->localBase));

        return '(?:' . $baseUrl .')?';
    }

    /**
     * Scope directories for regular expression
     *
     * @return string
     */
    protected function formatTargetDirsForRegex()
    {
        return implode('|', $this->includeDirs->each(function ($dir) {
            $dir = trim($dir);
            $dir = quotemeta($dir);
        })->toArray());
    }

    /**
     * Return protocol-relative url
     *
     * @param  string  $url
     * @return string
     */
    protected function relativizeUrl(string $url) {
        return substr($url, strpos($url, '//'));
    }
}
