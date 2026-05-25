<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Support\Uploads\UploadInspector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class ProcessUploadedImage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $uploadId)
    {
    }

    public function handle(UploadInspector $inspector): void
    {
        $upload = Upload::query()->find($this->uploadId);

        if (!$upload || $upload->type !== 'image') {
            return;
        }

        $result = $inspector->inspectStoredPath(
            (string) $upload->file_name,
            (string) $upload->type,
            (string) $upload->extension
        );

        if (Schema::hasColumn('uploads', 'file_hash') && !empty($result['hash'])) {
            $upload->file_hash = $result['hash'];
        }

        if (Schema::hasColumn('uploads', 'mime_type') && !empty($result['mime_type'])) {
            $upload->mime_type = $result['mime_type'];
        }

        if (Schema::hasColumn('uploads', 'processing_status')) {
            $upload->processing_status = !empty($result['valid_image']) ? 'ready' : 'failed';
        }

        if (Schema::hasColumn('uploads', 'processing_error')) {
            $upload->processing_error = !empty($result['valid_image'])
                ? null
                : ((string) ($result['error'] ?? 'image_processing_failed'));
        }

        $upload->save();
    }
}
