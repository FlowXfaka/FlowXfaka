<?php

namespace App\Models;

use App\Support\RichTextSanitizer;
use App\Support\StorefrontTheme;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    private const DEFAULT_TOPBAR_PRIMARY = '#704c84';
    private const DEFAULT_TOPBAR_SECONDARY = '#c67e8e';
    private const DEFAULT_TOPBAR_ACCENT = '#744e96';

    protected $fillable = [
        'site_name',
        'brand_icon_mode',
        'brand_icon_path',
        'background_mode',
        'background_image_path',
        'background_palette_primary',
        'background_palette_secondary',
        'background_palette_accent',
        'frontend_text_mode',
        'frontend_theme',
        'notice_html',
        'low_stock_threshold',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name' => config('app.name'),
                'brand_icon_mode' => 'default',
                'brand_icon_path' => null,
                'background_mode' => 'default',
                'background_image_path' => null,
                'background_palette_primary' => null,
                'background_palette_secondary' => null,
                'background_palette_accent' => null,
                'frontend_text_mode' => 'light',
                'frontend_theme' => StorefrontTheme::defaultTheme(),
                'notice_html' => '<p>&#24403;&#21069;&#39318;&#39029;&#21830;&#21697;&#20998;&#31867;&#19982;&#21830;&#21697;&#21015;&#34920;&#24050;&#25509;&#20837;&#30495;&#23454;&#25968;&#25454;&#24211;&#65292;&#21069;&#21488;&#23637;&#31034;&#19982;&#21518;&#21488;&#25490;&#24207;&#12289;&#26032;&#22686;&#21830;&#21697;&#20445;&#25345;&#21516;&#27493;&#12290;</p>',
                'low_stock_threshold' => 10,
            ]
        );
    }

    public function resolvedSiteName(): string
    {
        $name = trim((string) $this->site_name);

        return $name !== '' ? $name : config('app.name');
    }

    public function resolvedBackgroundPath(): string
    {
        if ($this->background_mode === 'custom' && is_string($this->background_image_path) && trim($this->background_image_path) !== '') {
            return trim($this->background_image_path);
        }

        return 'site-background.webp';
    }

    public function resolvedBrandIconPath(): ?string
    {
        if ($this->brand_icon_mode === 'custom' && is_string($this->brand_icon_path) && trim($this->brand_icon_path) !== '') {
            return trim($this->brand_icon_path);
        }

        return null;
    }

    public function resolvedBrandIconAssetPath(): string
    {
        return $this->resolvedBrandIconPath() ?? 'site-brand-icon.png';
    }

    public function resolvedFrontendTextMode(): string
    {
        $mode = trim((string) $this->frontend_text_mode);

        return in_array($mode, ['light', 'dark'], true) ? $mode : 'light';
    }

    public function resolvedFrontendTheme(): string
    {
        return StorefrontTheme::resolve((string) $this->frontend_theme);
    }

    public function resolvedNoticeHtml(): string
    {
        $html = trim((string) $this->notice_html);

        if ($html !== '') {
            return RichTextSanitizer::sanitize($html);
        }

        return '<p>&#24403;&#21069;&#39318;&#39029;&#21830;&#21697;&#20998;&#31867;&#19982;&#21830;&#21697;&#21015;&#34920;&#24050;&#25509;&#20837;&#30495;&#23454;&#25968;&#25454;&#24211;&#65292;&#21069;&#21488;&#23637;&#31034;&#19982;&#21518;&#21488;&#25490;&#24207;&#12289;&#26032;&#22686;&#21830;&#21697;&#20445;&#25345;&#21516;&#27493;&#12290;</p>';
    }

    public function resolvedBackgroundPalette(): array
    {
        return [
            'primary' => $this->normalizeHexColor($this->background_palette_primary, self::DEFAULT_TOPBAR_PRIMARY),
            'secondary' => $this->normalizeHexColor($this->background_palette_secondary, self::DEFAULT_TOPBAR_SECONDARY),
            'accent' => $this->normalizeHexColor($this->background_palette_accent, self::DEFAULT_TOPBAR_ACCENT),
        ];
    }

    public function resolvedStorefrontThemeStyle(string $backgroundImageUrl): string
    {
        $palette = $this->resolvedBackgroundPalette();
        $escapedUrl = str_replace(['\\', '\''], ['\\\\', '\\\''], $backgroundImageUrl);

        return implode(' ', [
            "--site-background-image: url('{$escapedUrl}');",
            '--storefront-topbar-base: ' . $this->hexToRgba($palette['accent'], 0.42) . ';',
            '--storefront-topbar-overlay-start: ' . $this->hexToRgba($palette['primary'], 0.44) . ';',
            '--storefront-topbar-overlay-mid: ' . $this->hexToRgba($palette['accent'], 0.28) . ';',
            '--storefront-topbar-overlay-end: ' . $this->hexToRgba($palette['secondary'], 0.36) . ';',
            '--storefront-topbar-glow-a: ' . $this->hexToRgba($palette['primary'], 0.14) . ';',
            '--storefront-topbar-glow-b: ' . $this->hexToRgba($palette['secondary'], 0.08) . ';',
        ]);
    }

    private function normalizeHexColor(mixed $value, string $fallback): string
    {
        $color = strtoupper(trim((string) $value));

        if (preg_match('/^#[0-9A-F]{6}$/', $color) === 1) {
            return $color;
        }

        return strtoupper($fallback);
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $normalized = ltrim($hex, '#');
        $red = hexdec(substr($normalized, 0, 2));
        $green = hexdec(substr($normalized, 2, 2));
        $blue = hexdec(substr($normalized, 4, 2));

        return sprintf('rgba(%d, %d, %d, %.2F)', $red, $green, $blue, $alpha);
    }
}
