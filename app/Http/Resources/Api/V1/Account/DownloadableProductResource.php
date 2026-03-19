<?php

namespace App\Http\Resources\Api\V1\Account;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class DownloadableProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $upload = null;

        if ($this->file_name && Schema::hasTable('uploads')) {
            $upload = Upload::query()->find($this->file_name);
        }

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'image' => $this->thumbnail_img ? api_asset($this->thumbnail_img) : '',
            'file_path' => $upload
                ? (app('router')->has('uploads.file')
                    ? $upload->download_url
                    : url('/storage/' . ltrim((string) $upload->file_name, '/')))
                : null,
        ];
    }
}
