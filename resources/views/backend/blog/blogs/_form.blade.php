@php
    $defaultLanguage = env('DEFAULT_LANGUAGE');
    $isDefaultLanguage = !isset($lang) || $lang === $defaultLanguage;
@endphp

<div class="form-group row">
    <label class="col-md-3 col-form-label">
        {{ translate('Blog Title') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <input
            type="text"
            placeholder="{{ translate('Blog Title') }}"
            onkeyup="makeSlug(this.value)"
            id="title"
            name="title"
            value="{{ old('title', isset($blog) ? $blog->getTranslation('title', $lang ?? $defaultLanguage) : '') }}"
            class="form-control"
            required
        >
    </div>
</div>

<div class="form-group row" id="category">
    <label class="col-md-3 col-from-label">
        {{ translate('Category') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <select class="form-control aiz-selectpicker" name="category_id" id="category_id" data-live-search="true" required>
            <option value="">{{ translate('Choose One') }}</option>
            @foreach ($blog_categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected((string) old('category_id', isset($blog) ? $blog->category_id : '') === (string) $category->id)
                >
                    {{ $category->getTranslation('name') }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Author') }}</label>
    <div class="col-md-9">
        <select class="form-control aiz-selectpicker" name="author_user_id" data-live-search="true">
            <option value="">{{ translate('Choose One') }}</option>
            @foreach ($authors as $author)
                <option
                    value="{{ $author->id }}"
                    @selected((string) old('author_user_id', isset($blog) ? $blog->author_user_id : '') === (string) $author->id)
                >
                    {{ $author->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">{{ translate('Frontend will display only the first name.') }}</small>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">
        {{ translate('Slug') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <input
            type="text"
            placeholder="{{ translate('Slug') }}"
            name="slug"
            id="slug"
            value="{{ old('slug', $blog->slug ?? '') }}"
            class="form-control"
            required
        >
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Published Date') }}</label>
    <div class="col-md-9">
        <input
            type="datetime-local"
            class="form-control"
            name="published_at"
            value="{{ old('published_at', isset($blog) && $blog->published_at ? $blog->published_at->format('Y-m-d\\TH:i') : '') }}"
        >
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Hero Button Label') }}</label>
    <div class="col-md-9">
        <input
            type="text"
            class="form-control"
            name="hero_button_label"
            value="{{ old('hero_button_label', isset($blog) ? $blog->getTranslation('hero_button_label', $lang ?? $defaultLanguage) : 'Read') }}"
            placeholder="{{ translate('Read') }}"
        >
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="signinSrEmail">
        {{ translate('Featured Image') }}
        <small>(1300x650)</small>
    </label>
    <div class="col-md-9">
        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary font-weight-medium">
                    {{ translate('Browse') }}
                </div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="banner" class="selected-files" value="{{ old('banner', $blog->banner ?? '') }}">
        </div>
        <div class="file-preview box sm"></div>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">
        {{ translate('Short Description') }}
    </label>
    <div class="col-md-9">
        <textarea name="short_description" rows="5" class="form-control">{{ old('short_description', isset($blog) ? $blog->getTranslation('short_description', $lang ?? $defaultLanguage) : '') }}</textarea>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-from-label">{{ translate('Description') }}</label>
    <div class="col-md-9">
        <textarea class="aiz-text-editor" name="description">{{ old('description', isset($blog) ? $blog->getTranslation('description', $lang ?? $defaultLanguage) : '') }}</textarea>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Modal Summary') }}</label>
    <div class="col-md-9">
        <textarea name="modal_summary" rows="4" class="form-control">{{ old('modal_summary', isset($blog) ? $blog->getTranslation('modal_summary', $lang ?? $defaultLanguage) : '') }}</textarea>
    </div>
</div>

<hr>
<h5 class="mb-3">{{ translate('SEO Meta') }}</h5>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Meta Title') }}</label>
    <div class="col-md-9">
        <input type="text" class="form-control" name="meta_title" value="{{ old('meta_title', $blog->meta_title ?? '') }}" placeholder="{{ translate('Meta Title') }}">
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="signinSrEmail">
        {{ translate('Meta Image') }}
        <small>(200x200)+</small>
    </label>
    <div class="col-md-9">
        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary font-weight-medium">
                    {{ translate('Browse') }}
                </div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="meta_img" class="selected-files" value="{{ old('meta_img', $blog->meta_img ?? '') }}">
        </div>
        <div class="file-preview box sm"></div>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Meta Description') }}</label>
    <div class="col-md-9">
        <textarea name="meta_description" rows="5" class="form-control">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Meta Keywords') }}</label>
    <div class="col-md-9">
        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $blog->meta_keywords ?? '') }}" placeholder="{{ translate('Meta Keywords') }}">
    </div>
</div>

<div class="form-group mb-0 text-right">
    <button type="submit" class="btn btn-primary">
        {{ translate('Save') }}
    </button>
</div>
