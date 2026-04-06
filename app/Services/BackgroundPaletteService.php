<?php

namespace App\Services;

class BackgroundPaletteService
{
    private const FALLBACK_PRIMARY = '#704C84';
    private const FALLBACK_SECONDARY = '#C67E8E';
    private const FALLBACK_ACCENT = '#744E96';
    private const SAMPLE_SIZE = 28;
    private const QUANTIZE_STEP = 24;

    public function extractFrom(string $path): array
    {
        $image = null;
        $sampled = null;

        try {
            $image = $this->loadImage($path);
            if (! $image) {
                return $this->fallbackPalette();
            }

            $sampled = $this->resampleImage($image, self::SAMPLE_SIZE, self::SAMPLE_SIZE);
            if (! $sampled) {
                return $this->fallbackPalette();
            }

            return $this->collectPalette($sampled);
        } catch (\Throwable) {
            return $this->fallbackPalette();
        } finally {
            $this->destroyImage($sampled);
            $this->destroyImage($image);
        }
    }

    private function fallbackPalette(): array
    {
        return [
            'background_palette_primary' => self::FALLBACK_PRIMARY,
            'background_palette_secondary' => self::FALLBACK_SECONDARY,
            'background_palette_accent' => self::FALLBACK_ACCENT,
        ];
    }

    private function loadImage(string $path)
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagesavealpha($image, true);

        return $image;
    }

    private function resampleImage($image, int $width, int $height)
    {
        $sampled = imagecreatetruecolor($width, $height);
        if (! $sampled) {
            return null;
        }

        imagealphablending($sampled, false);
        imagesavealpha($sampled, true);
        $transparent = imagecolorallocatealpha($sampled, 0, 0, 0, 127);
        imagefill($sampled, 0, 0, $transparent);

        imagecopyresampled(
            $sampled,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($image),
            imagesy($image)
        );

        return $sampled;
    }

    private function collectPalette($image): array
    {
        $histogram = [];
        $weightedSum = ['red' => 0.0, 'green' => 0.0, 'blue' => 0.0, 'weight' => 0.0];
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha >= 115) {
                    continue;
                }

                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                [$hue, $saturation, $value] = $this->rgbToHsv($red, $green, $blue);
                $luminance = $this->luminance($red, $green, $blue);
                $weight = 1.0 + ($saturation * 0.9) + (($value > 0.16 && $value < 0.92) ? 0.25 : 0.0);

                $weightedSum['red'] += $red * $weight;
                $weightedSum['green'] += $green * $weight;
                $weightedSum['blue'] += $blue * $weight;
                $weightedSum['weight'] += $weight;

                $bucketKey = $this->quantize($red) . '-' . $this->quantize($green) . '-' . $this->quantize($blue);

                if (! isset($histogram[$bucketKey])) {
                    $histogram[$bucketKey] = [
                        'red' => 0.0,
                        'green' => 0.0,
                        'blue' => 0.0,
                        'count' => 0,
                        'score' => 0.0,
                    ];
                }

                $histogram[$bucketKey]['red'] += $red;
                $histogram[$bucketKey]['green'] += $green;
                $histogram[$bucketKey]['blue'] += $blue;
                $histogram[$bucketKey]['count']++;
                $histogram[$bucketKey]['score'] += $weight + max(0, 0.35 - abs($luminance - 128) / 255);
            }
        }

        if ($histogram === [] || $weightedSum['weight'] <= 0) {
            return $this->fallbackPalette();
        }

        uasort($histogram, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        $selected = [];
        foreach ($histogram as $item) {
            $color = [
                'red' => (int) round($item['red'] / max(1, $item['count'])),
                'green' => (int) round($item['green'] / max(1, $item['count'])),
                'blue' => (int) round($item['blue'] / max(1, $item['count'])),
            ];
            $color = $this->normalizeColorTone($color);

            if ($this->isDistinct($color, $selected)) {
                $selected[] = $color;
            }

            if (count($selected) >= 2) {
                break;
            }
        }

        $accent = $this->normalizeColorTone([
            'red' => (int) round($weightedSum['red'] / $weightedSum['weight']),
            'green' => (int) round($weightedSum['green'] / $weightedSum['weight']),
            'blue' => (int) round($weightedSum['blue'] / $weightedSum['weight']),
        ]);

        $primary = $selected[0] ?? $this->hexToRgb(self::FALLBACK_PRIMARY);
        $secondary = $selected[1] ?? $this->blendColors($primary, $accent, 0.45);

        return [
            'background_palette_primary' => $this->rgbToHex($primary),
            'background_palette_secondary' => $this->rgbToHex($secondary),
            'background_palette_accent' => $this->rgbToHex($accent),
        ];
    }

    private function quantize(int $value): int
    {
        return (int) (round($value / self::QUANTIZE_STEP) * self::QUANTIZE_STEP);
    }

    private function normalizeColorTone(array $color): array
    {
        [$hue, $saturation, $value] = $this->rgbToHsv($color['red'], $color['green'], $color['blue']);

        $saturation = min(0.82, max(0.24, $saturation));
        $value = min(0.82, max(0.36, $value));

        return $this->hsvToRgb($hue, $saturation, $value);
    }

    private function isDistinct(array $candidate, array $selected): bool
    {
        foreach ($selected as $existing) {
            $distance = sqrt(
                (($candidate['red'] - $existing['red']) ** 2)
                + (($candidate['green'] - $existing['green']) ** 2)
                + (($candidate['blue'] - $existing['blue']) ** 2)
            );

            if ($distance < 48) {
                return false;
            }
        }

        return true;
    }

    private function blendColors(array $first, array $second, float $weightFirst): array
    {
        $weightSecond = 1 - $weightFirst;

        return $this->normalizeColorTone([
            'red' => (int) round(($first['red'] * $weightFirst) + ($second['red'] * $weightSecond)),
            'green' => (int) round(($first['green'] * $weightFirst) + ($second['green'] * $weightSecond)),
            'blue' => (int) round(($first['blue'] * $weightFirst) + ($second['blue'] * $weightSecond)),
        ]);
    }

    private function luminance(int $red, int $green, int $blue): float
    {
        return (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);
    }

    private function rgbToHex(array $color): string
    {
        return sprintf('#%02X%02X%02X', $color['red'], $color['green'], $color['blue']);
    }

    private function hexToRgb(string $hex): array
    {
        $normalized = ltrim($hex, '#');

        return [
            'red' => hexdec(substr($normalized, 0, 2)),
            'green' => hexdec(substr($normalized, 2, 2)),
            'blue' => hexdec(substr($normalized, 4, 2)),
        ];
    }

    private function rgbToHsv(int $red, int $green, int $blue): array
    {
        $red /= 255;
        $green /= 255;
        $blue /= 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $delta = $max - $min;

        if ($max <= 0.0) {
            return [0.0, 0.0, 0.0];
        }

        $hue = 0.0;
        if ($delta > 0) {
            if ($max === $red) {
                $hue = 60 * fmod((($green - $blue) / $delta), 6);
            } elseif ($max === $green) {
                $hue = 60 * ((($blue - $red) / $delta) + 2);
            } else {
                $hue = 60 * ((($red - $green) / $delta) + 4);
            }
        }

        if ($hue < 0) {
            $hue += 360;
        }

        $saturation = $delta / $max;

        return [$hue, $saturation, $max];
    }

    private function destroyImage(mixed &$image): void
    {
        if (function_exists('imagedestroy') && (is_object($image) || is_resource($image))) {
            @imagedestroy($image);
        }

        $image = null;
    }

    private function hsvToRgb(float $hue, float $saturation, float $value): array
    {
        $chroma = $value * $saturation;
        $segment = ($hue / 60);
        $intermediate = $chroma * (1 - abs(fmod($segment, 2) - 1));
        $match = $value - $chroma;

        if ($segment < 1) {
            [$red, $green, $blue] = [$chroma, $intermediate, 0];
        } elseif ($segment < 2) {
            [$red, $green, $blue] = [$intermediate, $chroma, 0];
        } elseif ($segment < 3) {
            [$red, $green, $blue] = [0, $chroma, $intermediate];
        } elseif ($segment < 4) {
            [$red, $green, $blue] = [0, $intermediate, $chroma];
        } elseif ($segment < 5) {
            [$red, $green, $blue] = [$intermediate, 0, $chroma];
        } else {
            [$red, $green, $blue] = [$chroma, 0, $intermediate];
        }

        return [
            'red' => (int) round(($red + $match) * 255),
            'green' => (int) round(($green + $match) * 255),
            'blue' => (int) round(($blue + $match) * 255),
        ];
    }
}
