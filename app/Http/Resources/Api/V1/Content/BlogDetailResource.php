<?php

namespace App\Http\Resources\Api\V1\Content;

use App\Services\Content\ContentMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = app(ContentMedia::class);
        $publishedAt = $this->published_at_or_created;

        return [
            'id' => $this->id,
            'category' => optional($this->category)->getTranslation('name'),
            'title' => $this->getTranslation('title'),
            'short_description' => $this->getTranslation('short_description'),
            'description' => $this->getTranslation('description'),
            'banner' => $media->asset($this->banner),
            'editorial_image' => $media->asset($this->editorial_image),
            'slug' => $this->slug,
            'author' => $this->author_first_name ?: $this->getAttribute('author') ?: $this->getAttribute('source_handle') ?: $this->getAttribute('source'),
            'hero_button_label' => $this->getTranslation('hero_button_label') ?: 'Read',
            'editorial_title' => $this->getTranslation('editorial_title'),
            'editorial_content' => $this->getTranslation('editorial_content'),
            'modal_summary' => $this->getTranslation('modal_summary'),
            'meta_title' => $this->meta_title,
            'meta_img' => $media->asset($this->meta_img),
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'created_at' => $publishedAt ? date('F j, Y', strtotime((string) $publishedAt)) : null,
            'published_at' => $publishedAt,
        ];
    }
}
