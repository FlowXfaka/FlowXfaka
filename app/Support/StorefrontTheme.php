<?php

namespace App\Support;

class StorefrontTheme
{
    public static function themes(): array
    {
        $themes = config('storefront.themes', []);

        return is_array($themes) && $themes !== []
            ? $themes
            : ['default' => '默认模板'];
    }

    public static function defaultTheme(): string
    {
        return (string) (array_key_first(static::themes()) ?? 'default');
    }

    public static function resolve(?string $theme = null): string
    {
        $resolvedTheme = trim((string) $theme);

        return array_key_exists($resolvedTheme, static::themes())
            ? $resolvedTheme
            : static::defaultTheme();
    }

    public static function view(string $template, ?string $theme = null): string
    {
        $resolvedTheme = static::resolve($theme);
        $candidate = 'themes.' . $resolvedTheme . '.' . ltrim($template, '.');

        if (view()->exists($candidate)) {
            return $candidate;
        }

        $fallback = 'themes.' . static::defaultTheme() . '.' . ltrim($template, '.');

        if (view()->exists($fallback)) {
            return $fallback;
        }

        return $template;
    }

    public static function assetPath(string $asset, ?string $theme = null): string
    {
        $resolvedTheme = static::resolve($theme);
        $normalizedAsset = ltrim($asset, '/\\');
        $candidate = public_path('themes/' . $resolvedTheme . '/' . $normalizedAsset);

        if (is_file($candidate)) {
            return 'themes/' . $resolvedTheme . '/' . $normalizedAsset;
        }

        $fallback = public_path('themes/' . static::defaultTheme() . '/' . $normalizedAsset);

        if (is_file($fallback)) {
            return 'themes/' . static::defaultTheme() . '/' . $normalizedAsset;
        }

        return $normalizedAsset;
    }

    public static function assetUrl(string $asset, ?string $theme = null): string
    {
        return asset(static::assetPath($asset, $theme));
    }

    public static function assetVersion(string $asset, ?string $theme = null): int
    {
        $path = public_path(static::assetPath($asset, $theme));

        return (int) (@filemtime($path) ?: time());
    }
}
