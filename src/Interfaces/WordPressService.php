<?php

namespace TinyPixel\Acorn\CDN\Interfaces;

interface WordPressService
{
    public function useWordPressHooks();

    public function filterAssets(array $action);

    public function filterContent(array $action);

    public function isWordPressPreview();
}
