<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;

class Blog extends Model
{ 
    protected $with = ['blog_translations'];

    protected $casts = [
        'published_at' => 'datetime',
        'related_product_ids' => 'array',
        'youtube_urls' => 'array',
    ];

    public function getTranslation($field = '', $lang = false)
    {
      $lang = $lang == false ? App::getLocale() : $lang;
      $blog_translation = $this->blog_translations->where('lang', $lang)->first();
      return $blog_translation != null ? $blog_translation->$field : $this->$field;
    }

    public function blog_translations()
    {
        return $this->hasMany(BlogTranslation::class);
    }
  

    public function category() {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function productBrand()
    {
        return $this->belongsTo(Brand::class, 'product_brand_id');
    }

    public function getPublishedAtOrCreatedAttribute()
    {
        return $this->published_at ?: $this->created_at;
    }

    public function getAuthorFirstNameAttribute(): string
    {
        return (string) optional($this->author)->first_name;
    }

}
