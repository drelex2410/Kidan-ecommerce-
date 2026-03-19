@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{ translate('Update Blog Information') }}</h5>
</div>

<div class="col-lg-10 mx-auto">
    <div class="card">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-fill border-light">
                @foreach (\App\Models\Language::where('status',1)->get() as $key => $language)
                    <li class="nav-item">
                        <a class="nav-link text-reset @if ($language->code == $lang) active @else bg-soft-dark border-light border-left-0 @endif py-3" href="{{ route('blog.edit', ['id' => $blog->id, 'lang' => $language->code]) }}">
                            <img src="{{ static_asset('assets/img/flags/'.$language->flag.'.png') }}" height="11" class="mr-1">
                            <span>{{ $language->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <form id="add_form" class="form-horizontal p-4" action="{{ route('blog.update', $blog->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="lang" value="{{ $lang }}">
                @include('backend.blog.blogs._form')
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
    @include('backend.blog.blogs._form_script')
@endsection
