<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BlogCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $publishedAt = $data->published_at_or_created;
                $author = $data->author_first_name ?: $data->getAttribute('author') ?: $data->getAttribute('source_handle') ?: $data->getAttribute('source');

                return [
                    'id' => $data->id,
                    'category' => optional($data->category)->getTranslation('name'),
                    'title' => $data->getTranslation('title'),
                    'short_description' => $data->getTranslation('short_description'),
                    'banner' => api_asset($data->banner),
                    'editorial_image' => $data->editorial_image ? api_asset($data->editorial_image) : null,
                    'slug' => $data->slug,
                    'type' => $data->getAttribute('type') ?: $data->getAttribute('post_type'),
                    'author' => $author,
                    'created_at' => $publishedAt ? date('F j, Y', strtotime($publishedAt)) : null,
                    'published_at' => $publishedAt,
                    'hero_button_label' => $data->getTranslation('hero_button_label') ?: 'Read',
                    'editorial_title' => $data->getTranslation('editorial_title'),
                    'editorial_content' => $data->getTranslation('editorial_content'),
                    'modal_summary' => $data->getTranslation('modal_summary'),
                    'has_videos' => !empty($data->youtube_urls),
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
