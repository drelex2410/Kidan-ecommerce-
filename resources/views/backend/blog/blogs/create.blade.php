@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Blog Information') }}</h5>
            </div>
            <div class="card-body">
                <form id="add_form" class="form-horizontal" action="{{ route('blog.store') }}" method="POST">
                    @csrf
                    @include('backend.blog.blogs._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    @include('backend.blog.blogs._form_script')
@endsection
