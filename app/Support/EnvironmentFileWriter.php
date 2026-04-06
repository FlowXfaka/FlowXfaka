<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class EnvironmentFileWriter
{
    public function path(): string
    {
        return base_path('.env');
    }

    public function templatePath(): string
    {
        return base_path('.env.example');
    }

    public function currentValue(string $key): ?string
    {
        if (! File::exists($this->path())) {
            return null;
        }

        $pattern = '/^'.preg_quote($key, '/').'\s*=\s*(.*)$/m';
        $contents = File::get($this->path());

        if (preg_match($pattern, $contents, $matches) !== 1) {
            return null;
        }

        return $this->normalizeExtractedValue($matches[1]);
    }

    /**
     * @param  array<string, bool|int|string|null>  $values
     */
    public function write(array $values): void
    {
        $path = $this->path();
        $contents = $this->baseContents();

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->stringify($value);
            $pattern = '/^'.preg_quote($key, '/').'\s*=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $line, $contents, 1);

                continue;
            }

            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        File::put($path, rtrim($contents).PHP_EOL);
    }

    private function baseContents(): string
    {
        if (File::exists($this->path())) {
            return File::get($this->path());
        }

        if (File::exists($this->templatePath())) {
            return File::get($this->templatePath());
        }

        return '';
    }

    private function normalizeExtractedValue(string $value): string
    {
        $normalized = trim($value);

        if (
            strlen($normalized) >= 2
            && $normalized[0] === '"'
            && $normalized[strlen($normalized) - 1] === '"'
        ) {
            return stripcslashes(substr($normalized, 1, -1));
        }

        return $normalized;
    }

    private function stringify(bool|int|string|null $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $string = (string) $value;

        if ($string === '') {
            return '';
        }

        if (in_array(strtolower($string), ['true', 'false', 'null', 'empty'], true)) {
            return '"'.addcslashes($string, "\\\"\n\r\t$").'"';
        }

        if (preg_match('/^[A-Za-z0-9_:@.\/\-]+$/', $string) === 1) {
            return $string;
        }

        return '"'.addcslashes($string, "\\\"\n\r\t$").'"';
    }
}
