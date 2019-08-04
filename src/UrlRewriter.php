<?php

namespace TinyPixel\Acorn\CDN;

use Roots\Acorn\Application;
use Illuminate\Support\Collection;
use TinyPixel\Support\Utility;
use TinyPixel\Acorn\CDN\Concerns\Cache;
use TinyPixel\Acorn\CDN\Concerns\WordPress;
use TinyPixel\Acorn\CDN\Interfaces\CacheableService;
use TinyPixel\Acorn\CDN\Interfaces\WordPressService;

/**
 * Url Rewriter
 *
 * Replaces domain of local WordPress assets
 * with a domain provided by a CDN service.
 *
 * @author  Kelly Mears <kelly@tinypixel.dev>
 * @license MIT
 * @since   1.0.0
 *
 * @package    WordPress
 * @subpackage AcornCDN
 */
class UrlRewriter implements CacheableService, WordPressService
{
    use WordPress, Cache;

    /**
     * CDN asset base
     *
     * @var string
     */
    protected $cdnBase;

    /**
     * Local asset base
     *
     * @var string
     */
    protected $localBase;

    /**
     * Include directories
     *
     * @var Illuminate\Support\Collection
     */
    protected $includeDirs;

    /**
     * Exclude filetypes
     *
     * @var Illuminate\Support\Collection
     */
    protected $excludeTypes = ['php'];

    /**
     * Regular expression to find paired URLs
     *
     * @var string
     */
    protected $urlMatchPairExpression;

    /**
     * Initializes class
     *
     * @param  \Illuminate\Support\Collection $config
     * @return void
     */
    public function init(Collection $config)
    {
        $this->configureRewriteService($config);

        $this->formatRegularExpression();

        if ($this instanceof WordPressService) {
            $this->useWordPressHooks();
        }
    }

    /**
     * Configure service instance
     *
     * @param \Illuminate\Support\Collection $config
     * @return void
     */
    protected function configureRewriteService(Collection $config)
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
     * Processes URLs contained within larger content strings
     *
     * @param  string $html
     * @return string
     */
    public function rewriteMarkup($html)
    {
        if ($this instanceof WordPressService && $this->isWordPressPreview()) {
            return $html;
        }

        return preg_replace_callback($this->urlMatchPairExpression, [
            $this, 'rewriteUrl',
        ], $html);
    }

    /**
     * Replaces isolated URL strings
     *
     * @param  string $url
     * @return string
     */
    public function rewriteUrl(string $url)
    {
        if (!$this->isReplaceableUrl($url)) {
            return $url;
        }

        return str_replace($this->localBase, $this->cdnBase, $url);
    }

    /**
     * Returns true if cache criteria is met
     *
     * @param  string $url
     * @return bool
     */
    public function isReplaceableUrl(string $url)
    {
        if ($this->checkIfTypeExcluded($url)) {
            return false;
        }

        if ($this instanceof WordPressService && $this->isWordPressPreview()) {
            return false;
        }

        return true;
    }

    /**
     * Determines if a given URL is of a verboten type
     *
     * @param  string  $asset
     * @return bool    true if excluded
     */
    protected function checkIfTypeExcluded(string $asset)
    {
        $excludeAsset = false;

        $this->excludeTypes->each(function ($type) use (& $excludeAsset, $asset) {
            $excludeAsset = !! $type && stristr($asset, ".{$type}") != false ?? true;
        });

        return $excludeAsset;
    }

    /**
     * Constructs the regular expression used to replace the URL
     *
     * @return string
     */
    protected function formatRegularExpression()
    {
        $template = '#(?<=[(\"\'])%1$s/(?:((?:%2$s)[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

        $dirs = $this->formatTargetDirsForRegex();
        $url  = $this->formatBaseUrlForRegex();

        return sprintf($template, $url, $dirs);
    }

    /**
     * Format URL for use in regular expression
     *
     * @return string
     */
    protected function formatBaseUrlForRegex()
    {
        $relativeUrl = $this->relativizeUrl(quotemeta($this->localBase));

        $baseUrl = Utility::isSecure() ?
            '(https?:|)' . $relativeUrl :
            '(http:|)' . $relativeUrl;

        return '(?:' . $baseUrl . ')?';
    }

    /**
     * Format list of directories for use in regular expression
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
    protected function relativizeUrl(string $url)
    {
        return substr($url, strpos($url, '//'));
    }
}
