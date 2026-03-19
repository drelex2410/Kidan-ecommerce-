<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Page extends Model
{

    protected $with = ['page_translations'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $page_translation = $this->hasMany(PageTranslation::class)->where('lang', $lang)->first();
        return $page_translation != null ? $page_translation->$field : $this->$field;
    }

    public function page_translations()
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function visibleSections()
    {
        return $this->hasMany(PageSection::class)->where('is_visible', true)->orderBy('sort_order');
    }

    public function scopePublished($query)
    {
        if (! \Schema::hasColumn($this->getTable(), 'is_published')) {
            return $query;
        }

        return $query->where('is_published', true);
    }

    public function getFrontendPathAttribute()
    {
        if ($this->slug === 'about-us') {
            return '/about';
        }

        if ($this->slug === 'journal') {
            return '/journal';
        }

        if ($this->type === 'home_page') {
            return '/';
        }

        return '/page/' . $this->slug;
    }
}
