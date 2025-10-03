<?php

declare(strict_types=1);

namespace Viewer3DTest\Doubles;

use Laminas\View\Renderer\PhpRenderer;

/**
 * Lightweight view renderer for testing renderers without Omeka helpers.
 */
class DummyPhpRenderer extends PhpRenderer
{
    public function assetUrl(string $path, ?string $module = null): string
    {
        // Simulate Omeka's assetUrl for module assets
        $prefix = $module ? "/modules/{$module}/" : "/assets/";
        return $prefix . ltrim($path, '/');
    }

    public function plugin($name)
    {
        // Provide a callable settings plugin that returns the default
        if ($name === 'setting') {
            return function (string $key, $default = null) {
                return $default;
            };
        }
        return parent::plugin($name);
    }

    public function translate(string $message): string
    {
        return $message; // no-op translation
    }
}
