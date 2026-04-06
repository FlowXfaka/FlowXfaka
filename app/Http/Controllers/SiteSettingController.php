<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\BackgroundPaletteService;
use App\Services\EditorImageUploadService;
use App\Support\RichTextSanitizer;
use App\Support\StorefrontTheme;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiteSettingController extends Controller
{
    public function show(): View
    {
        return view('admin.settings', [
            'title' => '站点设置',
            'subtitle' => '管理前台站点名称、模板、图标、背景图和站点公告。',
            'siteSettings' => SiteSetting::current(),
            'storefrontThemes' => StorefrontTheme::themes(),
        ]);
    }

    public function update(Request $request, BackgroundPaletteService $paletteService): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'frontend_theme' => ['required', 'string', Rule::in(array_keys(StorefrontTheme::themes()))],
            'frontend_text_mode' => ['required', 'string', Rule::in(['light', 'dark'])],
            'brand_icon_mode' => ['required', 'string', Rule::in(['default', 'custom'])],
            'brand_icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif'],
            'remove_brand_icon' => ['nullable', 'boolean'],
            'background_mode' => ['required', 'string', Rule::in(['default', 'custom'])],
            'background_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif'],
            'background_image_url' => ['nullable', 'string', 'max:2048', 'url'],
            'remove_background_image' => ['nullable', 'boolean'],
            'notice_html' => ['nullable', 'string', 'max:65535'],
        ], $this->settingsValidationMessages());

        $settings = SiteSetting::current();
        $attributes = [
            'site_name' => trim((string) $data['site_name']),
            'frontend_theme' => StorefrontTheme::resolve((string) $data['frontend_theme']),
            'frontend_text_mode' => (string) $data['frontend_text_mode'],
            'notice_html' => $this->normalizeNoticeHtml((string) ($data['notice_html'] ?? '')),
        ];

        $useDefaultBrandIcon = $data['brand_icon_mode'] === 'default';
        $removeBrandIcon = (bool) ($data['remove_brand_icon'] ?? false);
        $newBrandIcon = $data['brand_icon'] ?? null;

        if ($useDefaultBrandIcon || $removeBrandIcon) {
            $this->deleteManagedSettingsAsset($settings->brand_icon_path);
            $attributes['brand_icon_mode'] = 'default';
            $attributes['brand_icon_path'] = null;
        } elseif ($newBrandIcon instanceof UploadedFile) {
            $this->deleteManagedSettingsAsset($settings->brand_icon_path);
            $attributes['brand_icon_mode'] = 'custom';
            $attributes['brand_icon_path'] = $this->storeManagedSettingsUpload($newBrandIcon, 'site-brand-icon');
        } else {
            $attributes['brand_icon_mode'] = $settings->resolvedBrandIconPath() ? 'custom' : 'default';
            $attributes['brand_icon_path'] = $settings->resolvedBrandIconPath();
        }

        $useDefaultBackground = $data['background_mode'] === 'default';
        $removeBackground = (bool) ($data['remove_background_image'] ?? false);
        $newBackgroundImage = $data['background_image'] ?? null;
        $newBackgroundImageUrl = trim((string) ($data['background_image_url'] ?? ''));

        if ($useDefaultBackground || $removeBackground) {
            $this->deleteManagedSettingsAsset($settings->background_image_path);
            $attributes['background_mode'] = 'default';
            $attributes['background_image_path'] = null;
            $attributes['background_palette_primary'] = null;
            $attributes['background_palette_secondary'] = null;
            $attributes['background_palette_accent'] = null;
        } elseif ($newBackgroundImage instanceof UploadedFile) {
            $this->deleteManagedSettingsAsset($settings->background_image_path);
            $storedPath = $this->storeManagedSettingsUpload($newBackgroundImage, 'site-background');
            $attributes['background_mode'] = 'custom';
            $attributes['background_image_path'] = $storedPath;
            $attributes = array_merge($attributes, $paletteService->extractFrom(public_path($storedPath)));
        } elseif ($newBackgroundImageUrl !== '') {
            $storedPath = $this->storeManagedRemoteImage($newBackgroundImageUrl, 'site-background');
            $this->deleteManagedSettingsAsset($settings->background_image_path);
            $attributes['background_mode'] = 'custom';
            $attributes['background_image_path'] = $storedPath;
            $attributes = array_merge($attributes, $paletteService->extractFrom(public_path($storedPath)));
        } else {
            $attributes['background_mode'] = $settings->background_image_path ? 'custom' : 'default';
            $attributes['background_image_path'] = $settings->background_image_path;

            if ($attributes['background_mode'] === 'custom' && is_string($attributes['background_image_path']) && trim($attributes['background_image_path']) !== '') {
                $attributes['background_palette_primary'] = $settings->background_palette_primary;
                $attributes['background_palette_secondary'] = $settings->background_palette_secondary;
                $attributes['background_palette_accent'] = $settings->background_palette_accent;

                if (
                    ! is_string($settings->background_palette_primary)
                    || ! is_string($settings->background_palette_secondary)
                    || ! is_string($settings->background_palette_accent)
                ) {
                    $attributes = array_merge(
                        $attributes,
                        $paletteService->extractFrom(public_path($attributes['background_image_path']))
                    );
                }
            } else {
                $attributes['background_palette_primary'] = null;
                $attributes['background_palette_secondary'] = null;
                $attributes['background_palette_accent'] = null;
            }
        }

        $settings->update($attributes);

        return redirect()
            ->route('admin.settings')
            ->with('settings_notice', '设置已保存。');
    }

    public function uploadEditorImage(Request $request, EditorImageUploadService $uploader): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:5120'],
        ], [
            'image.required' => '请先选择公告图片。',
            'image.file' => '公告图片上传内容必须是文件。',
            'image.uploaded' => '公告图片上传失败，请检查文件大小后重试。',
            'image.mimes' => '公告图片仅支持 jpg、jpeg、png、webp、gif、avif 格式。',
            'image.max' => '公告图片大小不能超过 5MB。',
        ]);

        return response()->json([
            'url' => $uploader->store($data['image'], 'uploads/settings/editor', 'settings-editor'),
        ]);
    }

    private function normalizeNoticeHtml(string $html): string
    {
        return RichTextSanitizer::sanitize($html);
    }

    private function settingsValidationMessages(): array
    {
        return [
            'site_name.required' => '请输入站点名称。',
            'site_name.max' => '站点名称不能超过 80 个字符。',
            'frontend_theme.required' => '请选择前端模板。',
            'frontend_theme.in' => '前端模板参数无效。',
            'frontend_text_mode.required' => '请选择前端文字模式。',
            'frontend_text_mode.in' => '前端文字模式参数无效。',
            'brand_icon_mode.required' => '请选择前端站点图标模式。',
            'brand_icon_mode.in' => '前端站点图标模式参数无效。',
            'brand_icon.file' => '站点图标上传内容必须是文件。',
            'brand_icon.uploaded' => '站点图标上传失败，请检查文件大小后重试。',
            'brand_icon.mimes' => '站点图标仅支持 jpg、jpeg、png、webp、gif、avif 格式。',
            'background_mode.required' => '请选择前端背景图模式。',
            'background_mode.in' => '前端背景图模式参数无效。',
            'background_image.file' => '背景图上传内容必须是文件。',
            'background_image.uploaded' => '背景图上传失败，请检查文件大小后重试。',
            'background_image.mimes' => '背景图仅支持 jpg、jpeg、png、webp、gif、avif 格式。',
            'background_image_url.url' => '请输入有效的图片链接。',
            'background_image_url.max' => '图片链接长度不能超过 2048 个字符。',
            'notice_html.max' => '站点公告内容不能超过 65535 个字符。',
        ];
    }

    private function storeManagedSettingsUpload(UploadedFile $file, string $prefix): string
    {
        $directory = public_path('uploads/settings');
        File::ensureDirectoryExists($directory);

        $extension = $file->extension() ?: ($file->getClientOriginalExtension() ?: 'png');
        $filename = $prefix . '-' . Str::lower((string) Str::ulid()) . '.' . strtolower($extension);
        $file->move($directory, $filename);

        return 'uploads/settings/' . $filename;
    }

    private function storeManagedRemoteImage(string $url, string $prefix): string
    {
        $cleanUrl = trim($url);

        if ($cleanUrl === '') {
            throw ValidationException::withMessages([
                'background_image_url' => '请输入图片链接。',
            ]);
        }

        $this->assertRemoteImageUrlAllowed($cleanUrl);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; flowx-background-fetcher/1.0)',
                ])
                ->get($cleanUrl);
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages([
                'background_image_url' => '图片链接抓取失败，请稍后重试。',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'background_image_url' => '图片链接抓取失败，请检查地址后重试。',
            ]);
        }

        $body = $response->body();

        if ($body === '') {
            throw ValidationException::withMessages([
                'background_image_url' => '图片链接内容为空，请更换地址后重试。',
            ]);
        }

        if (strlen($body) > 8 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'background_image_url' => '图片体积不能超过 8MB。',
            ]);
        }

        $mimeType = $this->detectImageMimeType($body);
        $extension = $this->extensionFromMimeType($mimeType);

        if ($extension === null) {
            throw ValidationException::withMessages([
                'background_image_url' => '链接内容不是受支持的图片格式。',
            ]);
        }

        return $this->storeManagedSettingsContents($body, $prefix, $extension);
    }

    private function detectImageMimeType(string $contents): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($contents) ?: null;

        return is_string($mimeType) ? strtolower(trim($mimeType)) : null;
    }

    private function extensionFromMimeType(?string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => null,
        };
    }

    private function storeManagedSettingsContents(string $contents, string $prefix, string $extension): string
    {
        $directory = public_path('uploads/settings');
        File::ensureDirectoryExists($directory);

        $filename = $prefix . '-' . Str::lower((string) Str::ulid()) . '.' . strtolower($extension);
        File::put($directory . DIRECTORY_SEPARATOR . $filename, $contents);

        return 'uploads/settings/' . $filename;
    }

    private function assertRemoteImageUrlAllowed(string $url): void
    {
        $parsed = parse_url($url);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = trim((string) ($parsed['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw ValidationException::withMessages([
                'background_image_url' => '请输入有效的 http 或 https 图片链接。',
            ]);
        }

        foreach ($this->resolveHostAddresses($host) as $address) {
            if ($this->isPublicIpAddress($address)) {
                continue;
            }

            throw ValidationException::withMessages([
                'background_image_url' => '图片链接地址不可用，请更换公开图片地址。',
            ]);
        }
    }

    private function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $addresses = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (! empty($record['ip'])) {
                $addresses[] = $record['ip'];
            }

            if (! empty($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbynamel($host);

            if (is_array($fallback)) {
                $addresses = $fallback;
            }
        }

        if ($addresses === []) {
            throw ValidationException::withMessages([
                'background_image_url' => '图片链接域名解析失败，请检查地址后重试。',
            ]);
        }

        return array_values(array_unique($addresses));
    }

    private function isPublicIpAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function deleteManagedSettingsAsset(?string $path): void
    {
        $relative = trim((string) $path);

        if ($relative === '' || ! str_starts_with($relative, 'uploads/settings/')) {
            return;
        }

        $full = public_path($relative);

        if (File::exists($full)) {
            File::delete($full);
        }
    }
}
