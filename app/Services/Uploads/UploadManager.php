<?php

namespace App\Services\Uploads;

use App\Jobs\ProcessUploadedImage;
use App\Models\Upload;
use App\Support\Uploads\UploadInspectionResult;
use App\Support\Uploads\UploadInspector;
use App\Support\Uploads\UploadStorage;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UploadManager
{
    public function __construct(
        private readonly UploadInspector $inspector,
        private readonly DatabaseManager $db,
    ) {
    }

    public function store(UploadedFile $file, ?int $userId = null): Upload
    {
        $inspection = $this->inspector->inspectIncoming($file);
        $storedPath = null;

        try {
            $storedPath = UploadStorage::store($file);

            if (!UploadStorage::exists($storedPath)) {
                throw new \RuntimeException('Stored file is missing immediately after upload.');
            }

            $absolutePath = UploadStorage::absolutePath($storedPath);
            if ($absolutePath && is_file($absolutePath)) {
                $storedSize = (int) (filesize($absolutePath) ?: 0);
                if ($storedSize <= 0 || $storedSize !== $inspection->size) {
                    throw new \RuntimeException('Stored file size does not match uploaded file size.');
                }

                if ($inspection->isImage()) {
                    $storedInspection = $this->inspector->inspectStoredPath(
                        $storedPath,
                        $inspection->type,
                        $inspection->extension
                    );

                    if (!($storedInspection['exists'] ?? false) || !($storedInspection['valid_image'] ?? false)) {
                        throw new \RuntimeException('Stored image failed integrity checks.');
                    }
                }
            }

            $upload = $this->db->transaction(function () use ($inspection, $storedPath, $userId) {
                $upload = new Upload();
                $upload->file_original_name = $inspection->displayName;
                $upload->file_name = $storedPath;
                $upload->user_id = $userId;
                $upload->extension = $inspection->extension;
                $upload->type = $inspection->type;
                $upload->file_size = $inspection->size;

                if (Schema::hasColumn('uploads', 'processing_status')) {
                    $upload->processing_status = $inspection->isImage() ? 'processing' : 'ready';
                }

                if (Schema::hasColumn('uploads', 'processing_error')) {
                    $upload->processing_error = null;
                }

                if (Schema::hasColumn('uploads', 'file_hash')) {
                    $upload->file_hash = $inspection->hash;
                }

                if (Schema::hasColumn('uploads', 'mime_type')) {
                    $upload->mime_type = $inspection->mimeType;
                }

                $upload->save();

                return $upload;
            });

            $this->dispatchPostProcessing($upload, $inspection);

            return $upload->fresh() ?? $upload;
        } catch (\Throwable $exception) {
            if ($storedPath) {
                UploadStorage::delete($storedPath);
            }

            Log::warning('Upload storage failed and was rolled back', [
                'message' => $exception->getMessage(),
                'original_name' => $file->getClientOriginalName(),
            ]);

            throw $exception;
        }
    }

    protected function dispatchPostProcessing(Upload $upload, UploadInspectionResult $inspection): void
    {
        if (!$inspection->isImage()) {
            if (Schema::hasColumn('uploads', 'processing_status')) {
                $upload->forceFill(['processing_status' => 'ready'])->save();
            }

            return;
        }

        ProcessUploadedImage::dispatch($upload->id)->afterCommit();
    }
}
