@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col">
			<h1 class="h3">{{ translate('Edit Page Information') }}</h1>
		</div>
	</div>
</div>
<div class="card">
	<ul class="nav nav-tabs nav-fill border-light">
		@foreach (\App\Models\Language::where('status',1)->get() as $key => $language)
			<li class="nav-item">
				<a class="nav-link text-reset @if ($language->code == $lang) active @else bg-soft-dark border-light border-left-0 @endif py-3" href="{{ route('custom-pages.edit', ['id'=>$page->slug, 'lang'=> $language->code] ) }}">
					<img src="{{ static_asset('assets/img/flags/'.$language->flag.'.png') }}" height="11" class="mr-1">
					<span>{{$language->name}}</span>
				</a>
			</li>
			@endforeach
		</ul>
		<form class="p-4" action="{{ route('custom-pages.update', $page->id) }}" method="POST" enctype="multipart/form-data">
			@csrf
			<input type="hidden" name="_method" value="PATCH">
			<input type="hidden" name="lang" value="{{ $lang }}">
			@php
				$sectionsForBuilder = old('sections', ($page->sections ?? collect())->map(function ($section) {
					return [
						'type' => $section->type,
						'title' => $section->title,
						'subtitle' => $section->subtitle,
						'content' => $section->content,
						'button_text' => $section->button_text,
						'button_link' => $section->button_link,
						'image' => $section->image,
						'image_2' => $section->image_2,
						'sort_order' => $section->sort_order,
						'is_visible' => $section->is_visible,
						'settings' => $section->settings_json ?? [],
					];
				})->values()->toArray());
			@endphp

			@include('backend.website_settings.pages._section_builder', [
				'page' => $page,
				'lang' => $lang,
				'sectionsForBuilder' => $sectionsForBuilder,
				'submitLabel' => translate('Update Page')
			])
		</form>
</div>
@endsection

@section('script')
	@stack('page_builder_scripts')
@endsection
