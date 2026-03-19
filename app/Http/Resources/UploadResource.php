<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_original_name' => $this->display_name,
            'display_name' => $this->display_name,
            'file_name' => $this->file_name,
            'normalized_file_name' => $this->normalized_file_name,
            'user_id' => $this->user_id,
            'file_size' => $this->file_size,
            'extension' => $this->extension,
            'type' => $this->type,
            'preview_url' => $this->preview_url,
            'download_url' => $this->download_url,
            'file_url' => $this->download_url,
            'is_previewable' => $this->is_previewable,
            'file_icon_class' => $this->file_icon_class,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
