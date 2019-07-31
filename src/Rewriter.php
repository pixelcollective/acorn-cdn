<?php

namespace TinyPixel\Acorn\CDN;

use Illuminate\Support\Collection;
use function Roots\add_filters;

/**
 * Asset Rewriter
 *
 * @author  Kelly Mears <kelly@tinypixel.dev>
 * @license MIT
 * @since   1.0.0
 *
 * @package    WordPress
 * @subpackage AcornCDN
 */
class Rewriter
{
    /**
     * CDN asset base
     * @var string
     */
    private $cdnBase;

    /**
     * Local asset base
     * @var string
     */
    private $localBase;

    /**
     * Filters
     * @var array
     */
    protected $filters = [
        'theme_root_uri',
        'plugins_url',
        'script_loader_src',
        'style_loader_src',
    ];

    /**
     * Include directories
     * @var Illuminate\Support\Collection
     */
    protected $includeDirs;

    /**
     * Exclude directories
     * @var Illuminate\Support\Collection
     */
    protected $excludeDirs;

    /**
     * SSL assets enabled
     * @var boolean
     */
    protected $sslEnabled;

    /**
     * Relative assets enabled
     * @var boolean
     */
    protected $relativeEnabled;

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
        $this->configureCDN();
        $this->processBaseUrls();
        $this->setHooks();
    }

    /**
     * Set WordPress actions & filters
     *
     * @return void
     */
    protected function setHooks()
    {
        add_filters($this->filters, [$this, 'handleString'], 99, 1);
    }

    /**
     * Handle arbitrary HTML
     *
     * @param  string $html
     * @return string
     */
    public function handleString($html)
    {
        $cacheId = intval($html, 62);

        $this->app['cache']::remember("cdn.{$cacheId}", $this->cacheExpiry, function () {
            if ($this->sslDisabled()) {
                return $html;
            }

            return preg_replace_callback($this->expression($localTarget), [
                $this, 'rewriteAsset',
            ], $html);
        });
    }

    /**
     * Configure CDN
     *
     * @return void
     */
    protected function configureCDN()
    {
        $this->siteUrl      = $config->get('local_url');
        $this->cdnUrl       = $config->get('cdn_url');
        $this->cacheTime    = $config->get('cache_expiry');

        $this->includeDirs  = Collection::make($config->get('include_directories'));
        $this->excludeDirs  = Collection::make($config->get('exclude_directories'));
    }

    /**
     * Replace URLs with CDN
     *
     * @param  string $url
     * @return string
     */
    protected function rewriteAsset(string $url)
    {
        if ($this->exclude_asset($url) || $this->isPreview()) {
            return $url;
        }

        if ($this->isProtocolRelative($url)) {
            return str_replace($this->localBase, $this->cdnBase, $url);
        }

        if (! $this->relativeEnabled || strstr($url, $baseUrl)) {
            return str_replace($this->baseProtocols, $this->cdnBase, $url);
        }

        return $this->cdnBase . $url;
    }

    /**
     * Process base URLs depending on if SSL and relative URLs are enabled
     *
     * @return array
     */
    protected function processBaseUrls()
    {
        $base = $this->relativizeUrl($this->baseUrl);

        if ($this->sslEnabled) {
            $this->baseProtocols = [
                'http:' . $base,
                'https:' . $base,
            ];

        } else {
            $this->baseProtocols = ['http:' . $base];
        }
    }

    /**
     * Determine if current page is post preview
     *
     * @return boolean
     */
    protected function isPreview()
    {
        if (is_admin_bar_showing()) {
            return (array_key_exists('preview', $_GET) && $_GET['preview'] == 'true') ?? true;
        }

        return false;
    }

    /**
     * Determine if URL is protocol-relative
     *
     * @return boolean
     */
    protected function isProtocolRelative($url)
    {
        return (strpos($url, '//') === 0) ?? true;
    }

    /**
     * Should SSL assets should be rewritten
     *
     * @return boolean
     */
    protected function sslDisabled()
    {
        if (! $this->sslEnabled) {
            return isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on';
        }
    }

    /**
     * Return relative url
     *
     * @param  string  $url
     * @return string
     */
    protected function relativizeUrl(string $url) {
        return substr($url, strpos($url, '//'));
    }

    /**
     * Exclude assets that should not be rewritten
     *
     * @param  string  $asset  current asset
     * @return boolean true if need to be excluded
     */
    protected function excludeAsset(&$asset, $doExclude = false) {
        $this->excludes->each(function ($exclude) use ($doExclude, $asset) {
            $doExclude = (!! $exclude && stristr($asset, $exclude) != false) ?? true;
        });

        return $doExclude;
    }

    /**
     * Regular expression for CDN URL replacement
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
        $baseUrl = $this->sslEnabled
            ? '(https?:|)' . $this->relativizeUrl(quotemeta($this->localBase))
            : '(http:|)' . $this->relativizeUrl(quotemeta($this->localBase));

        return $this->relativeEnabled ? '(?:' . $baseUrl .')?' : $baseUrl;
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
}
