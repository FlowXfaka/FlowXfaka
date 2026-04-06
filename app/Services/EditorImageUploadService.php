<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EditorImageUploadService
{
    public function store(UploadedFile $file, string $directory, string $prefix = 'editor'): string
    {
        $relativeDirectory = trim(str_replace('\\', '/', $directory), '/');
        $absoluteDirectory = public_path($relativeDirectory);

        File::ensureDirectoryExists($absoluteDirectory);

        $extension = strtolower($file->extension() ?: ($file->getClientOriginalExtension() ?: 'png'));
        $filename = trim($prefix) !== ''
            ? trim($prefix) . '-' . Str::lower((string) Str::ulid()) . '.' . $extension
            : Str::lower((string) Str::ulid()) . '.' . $extension;

        $file->move($absoluteDirectory, $filename);

        return '/' . trim($relativeDirectory . '/' . $filename, '/');
    }
}
