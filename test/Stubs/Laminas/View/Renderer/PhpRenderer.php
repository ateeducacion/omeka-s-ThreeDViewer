<?php

declare(strict_types=1);

namespace Laminas\View\Renderer;

class PhpRenderer
{
    public function headScript()
    {
        return new class {
            /** @var array<int, string> */
            public array $files = [];
            public function appendFile(string $url, $type = null): void
            {
                $this->files[] = $url;
            }
            public function appendScript(string $script): void
            {
            }
        };
    }

    public function inlineScript()
    {
        return $this->headScript();
    }

    public function headStyle()
    {
        return new class {
            public function appendStyle(string $style): void
            {
            }
        };
    }

    public function assetUrl(string $path, ?string $module = null): string
    {
        $prefix = $module ? "/modules/{$module}/" : "/assets/";
        return $prefix . ltrim($path, '/');
    }

    public function plugin(string $name)
    {
        if ($name === 'setting') {
            return function (string $key, $default = null) {
                return $default;
            };
        }
        return null;
    }

    public function translate(string $message): string
    {
        return $message;
    }

    public function escapeHtmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
