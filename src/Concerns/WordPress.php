<?php

namespace TinyPixel\Acorn\CDN\Concerns;

use function Roots\add_filters;

trait WordPress
{
    /**
     * Asset Filters
     *
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
     *
     * @var array
     */
    protected $markupFilters = [
        'the_content',
        'the_excerpt',
    ];

    /**
     * Use WordPress Hooks
     *
     * @return void
     */
    public function useWordPressHooks()
    {
        if ($this instanceof CacheableService) {
            $this->filterAssets([$this, 'cacheURL']);
            $this->filterContent([$this, 'cacheMarkup']);
        } else {
            $this->filterAssets([$this, 'rewriteUrl']);
            $this->filterContent([$this, 'rewriteMarkup']);
        }
    }

     /**
     * Sets WordPress actions & filters
     *
     * @param  array $action
     * @return void
     */
    public function filterAssets(array $action)
    {
        add_filters($this->assetFilters, $action, 99, 1);
    }

    /**
     * Applies filter to WordPress content filters
     *
     * @param  array $action
     * @return void
     */
    public function filterContent(array $action)
    {
        add_filters($this->markupFilters, $action, 99, 1);
    }

    /**
     * Return true if current request is for a post preview
     *
     * @param  bool $preview
     * @return bool $preview
     */
    public function isWordPressPreview($preview = false)
    {
        if (is_admin_bar_showing()) {
            $preview = array_key_exists('preview', $_GET) && $_GET['preview'] == 'true';
        }

        return $preview;
    }

    /**
     * Processes URLs contained within larger content strings
     *
     * @param  string $html
     * @return string
     */
    public function rewriteMarkup($html)
    {
        if ($this instanceOf WordPressService && $this->isWordPressPreview()) {
            return $html;
        }

        return preg_replace_callback($this->urlMatchExpression, [
            $this, 'rewriteUrl',
        ], $html);
    }
}
