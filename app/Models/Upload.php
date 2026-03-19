<?php

namespace App\Models;

use App\Support\Uploads\UploadStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Upload extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_original_name', 'file_name', 'user_id', 'extension', 'type', 'file_size',
    ];

    protected $appends = [
        'preview_url',
        'download_url',
        'is_previewable',
        'display_name',
        'file_icon_class',
        'normalized_file_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute()
    {
        $name = trim((string) $this->file_original_name);

        if ($name === '') {
            return translate('Unknown');
        }

        $extension = trim((string) $this->extension);
        if ($extension !== '') {
            $suffix = '.' . $extension;
            if (Str::endsWith(Str::lower($name), Str::lower($suffix))) {
                return substr($name, 0, -strlen($suffix));
            }
        }

        return $name;
    }

    public function getNormalizedFileNameAttribute()
    {
        return UploadStorage::normalizePath($this->file_name);
    }

    public function getPreviewUrlAttribute()
    {
        if (!$this->exists) {
            return null;
        }

        return route('uploads.file', ['upload' => $this->id]);
    }

    public function getDownloadUrlAttribute()
    {
        if (!$this->exists) {
            return null;
        }

        return route('uploads.file', ['upload' => $this->id, 'download' => 1]);
    }

    public function getIsPreviewableAttribute()
    {
        return $this->type === 'image' && $this->fileExists();
    }

    public function getFileIconClassAttribute()
    {
        return match ($this->type) {
            'video' => 'las la-file-video',
            'audio' => 'las la-file-audio',
            'archive' => 'las la-file-archive',
            'document' => 'las la-file-alt',
            default => 'las la-file',
        };
    }

    public function fileExists(): bool
    {
        return UploadStorage::exists($this->file_name);
    }

    public function absolutePath(): ?string
    {
        return UploadStorage::absolutePath($this->file_name);
    }

    public function deleteStoredFile(): void
    {
        UploadStorage::delete($this->file_name);
    }

    protected function existsOnPublicDisk(): bool
    {
        return UploadStorage::exists($this->file_name);
    }

    protected function usesS3(): bool
    {
        return UploadStorage::usesObjectStorage();
    }
}
