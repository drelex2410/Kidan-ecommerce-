@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col">
			<h1 class="h3">{{ translate('Add New Page') }}</h1>
		</div>
	</div>
</div>
<form action="{{ route('custom-pages.store') }}" method="POST" enctype="multipart/form-data">
	@csrf
	<input type="hidden" name="lang" value="{{ $lang }}">
	@include('backend.website_settings.pages._section_builder', [
		'page' => new \App\Models\Page(),
		'lang' => $lang,
		'sectionsForBuilder' => old('sections', []),
		'submitLabel' => translate('Save Page')
	])
</form>
@endsection

@section('script')
	@stack('page_builder_scripts')
@endsection
