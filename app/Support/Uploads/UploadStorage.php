<?php

namespace App\Support\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadStorage
{
    public const DIRECTORY = 'uploads/all';

    public static function extensionMap(): array
    {
        return [
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'svg' => 'image',
            'webp' => 'image',
            'gif' => 'image',
            'mp4' => 'video',
            'mpg' => 'video',
            'mpeg' => 'video',
            'webm' => 'video',
            'ogg' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'flv' => 'video',
            'swf' => 'video',
            'mkv' => 'video',
            'wmv' => 'video',
            'wma' => 'audio',
            'aac' => 'audio',
            'wav' => 'audio',
            'mp3' => 'audio',
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'doc' => 'document',
            'txt' => 'document',
            'docx' => 'document',
            'pdf' => 'document',
            'csv' => 'document',
            'xml' => 'document',
            'ods' => 'document',
            'xlr' => 'document',
            'xls' => 'document',
            'xlsx' => 'document',
        ];
    }

    public static function allowedExtensions(): array
    {
        return array_keys(static::extensionMap());
    }

    public static function allowedExtensionsString(): string
    {
        return implode(',', static::allowedExtensions());
    }

    public static function typeForExtension(?string $extension): ?string
    {
        if (blank($extension)) {
            return null;
        }

        return static::extensionMap()[Str::lower($extension)] ?? null;
    }

    public static function effectiveDisk(): string
    {
        return (string) (config('filesystems.default') ?: env('FILESYSTEM_DRIVER', 'local'));
    }

    public static function driver(?string $disk = null): string
    {
        $disk = $disk ?: static::effectiveDisk();

        return (string) config("filesystems.disks.{$disk}.driver", $disk);
    }

    public static function usesObjectStorage(?string $disk = null): bool
    {
        return static::driver($disk) === 's3';
    }

    public static function store(UploadedFile $file, ?string $disk = null): string
    {
        return $file->store(static::DIRECTORY, $disk ?: static::effectiveDisk());
    }

    public static function normalizePath($path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim(str_replace('\\', '/', (string) $path));

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $publicRoot = str_replace('\\', '/', public_path());
        $storageRoot = str_replace('\\', '/', storage_path('app/public'));

        foreach ([$publicRoot, $storageRoot] as $root) {
            if ($root !== '' && Str::startsWith($path, $root . '/')) {
                $path = Str::after($path, $root . '/');
                break;
            }
        }

        $path = ltrim($path, '/');

        foreach ([
            'public/',
            'storage/',
            'storage/app/public/',
            'app/public/',
        ] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $path = Str::after($path, $prefix);
                break;
            }
        }

        return ltrim($path, '/');
    }

    public static function exists($path): bool
    {
        $normalizedPath = static::normalizePath($path);

        if (blank($normalizedPath)) {
            return false;
        }

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return true;
        }

        if (static::usesObjectStorage()) {
            return Storage::disk(static::effectiveDisk())->exists($normalizedPath);
        }

        foreach (array_unique([static::effectiveDisk(), 'public']) as $disk) {
            if (static::driver($disk) === 'local' && Storage::disk($disk)->exists($normalizedPath)) {
                return true;
            }
        }

        return file_exists(public_path($normalizedPath)) || file_exists(storage_path('app/public/' . $normalizedPath));
    }

    public static function absolutePath($path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = str_replace('\\', '/', (string) $path);
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (Str::startsWith($path, ['/'])) {
            return file_exists($path) ? $path : null;
        }

        $normalizedPath = static::normalizePath($path);
        if (blank($normalizedPath) || static::usesObjectStorage()) {
            return null;
        }

        foreach (array_unique([static::effectiveDisk(), 'public']) as $disk) {
            if (static::driver($disk) !== 'local') {
                continue;
            }

            $candidate = Storage::disk($disk)->path($normalizedPath);
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        foreach ([public_path($normalizedPath), storage_path('app/public/' . $normalizedPath)] as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function delete($path): void
    {
        $normalizedPath = static::normalizePath($path);

        if (blank($normalizedPath) || filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return;
        }

        if (static::usesObjectStorage()) {
            Storage::disk(static::effectiveDisk())->delete($normalizedPath);
            return;
        }

        foreach (array_unique([static::effectiveDisk(), 'public']) as $disk) {
            if (static::driver($disk) === 'local' && Storage::disk($disk)->exists($normalizedPath)) {
                Storage::disk($disk)->delete($normalizedPath);
            }
        }

        foreach ([public_path($normalizedPath), storage_path('app/public/' . $normalizedPath)] as $candidate) {
            if (file_exists($candidate)) {
                @unlink($candidate);
            }
        }
    }

    public static function publicUrl($path, $secure = null): ?string
    {
        $normalizedPath = static::normalizePath($path);

        if (blank($normalizedPath)) {
            return null;
        }

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return $normalizedPath;
        }

        if (static::usesObjectStorage()) {
            return Storage::disk(static::effectiveDisk())->url($normalizedPath);
        }

        return app('url')->asset($normalizedPath, $secure);
    }
}
