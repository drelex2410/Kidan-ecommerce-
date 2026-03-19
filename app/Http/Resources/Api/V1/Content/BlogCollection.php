<?php

namespace App\Http\Resources\Api\V1\Content;

use App\Services\Content\ContentMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BlogCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $media = app(ContentMedia::class);

        return [
            'data' => $this->collection->map(function ($data) use ($media) {
                $publishedAt = $data->published_at_or_created;
                $author = $data->author_first_name ?: $data->getAttribute('author') ?: $data->getAttribute('source_handle') ?: $data->getAttribute('source');

                return [
                    'id' => $data->id,
                    'category' => optional($data->category)->getTranslation('name'),
                    'title' => $data->getTranslation('title'),
                    'short_description' => $data->getTranslation('short_description'),
                    'banner' => $media->asset($data->banner),
                    'editorial_image' => $media->asset($data->editorial_image),
                    'slug' => $data->slug,
                    'type' => $data->getAttribute('type') ?: $data->getAttribute('post_type'),
                    'author' => $author,
                    'created_at' => $publishedAt ? date('F j, Y', strtotime((string) $publishedAt)) : null,
                    'published_at' => $publishedAt,
                    'hero_button_label' => $data->getTranslation('hero_button_label') ?: 'Read',
                    'editorial_title' => $data->getTranslation('editorial_title'),
                    'editorial_content' => $data->getTranslation('editorial_content'),
                    'modal_summary' => $data->getTranslation('modal_summary'),
                    'has_videos' => !empty($data->youtube_urls),
                ];
            }),
        ];
    }
}
