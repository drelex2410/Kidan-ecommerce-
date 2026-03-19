<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogSingleCollection extends JsonResource
{   
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $publishedAt = $this->published_at_or_created;

        return [
            'id' => $this->id,
            'category' => optional($this->category)->getTranslation('name'),
            'title' => $this->getTranslation('title'),
            'short_description' => $this->getTranslation('short_description'),
            'description' => $this->getTranslation('description'),
            'banner' => api_asset($this->banner),
            'editorial_image' => $this->editorial_image ? api_asset($this->editorial_image) : null,
            'slug' => $this->slug,
            'author' => $this->author_first_name ?: $this->getAttribute('author') ?: $this->getAttribute('source_handle') ?: $this->getAttribute('source'),
            'hero_button_label' => $this->getTranslation('hero_button_label') ?: 'Read',
            'editorial_title' => $this->getTranslation('editorial_title'),
            'editorial_content' => $this->getTranslation('editorial_content'),
            'modal_summary' => $this->getTranslation('modal_summary'),
            'meta_title' => $this->meta_title,
            'meta_img' => $this->meta_img ? api_asset($this->meta_img) : null,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'created_at' => $publishedAt ? date('F j, Y', strtotime($publishedAt)) : null,
            'published_at' => $publishedAt,
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }

    protected function convertPhotos(){
        $result = array();
        foreach (explode(',', $this->photos) as $item) {
            array_push($result, api_asset($item));
        }
        return $result;
    }
}
