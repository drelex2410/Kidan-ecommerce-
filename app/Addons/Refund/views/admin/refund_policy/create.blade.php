@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Create Refund Policy') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('admin.refund_policies.index') }}" class="btn btn-soft-secondary btn-sm">
                    {{ translate('Back to Refund Policies') }}
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.refund_policies.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-10 mx-auto">
                @include('addon:refund::admin.refund_policy._form', [
                    'submitLabel' => translate('New Refund Policy'),
                    'buttonLabel' => translate('Save'),
                ])
            </div>
        </div>
    </form>
@endsection
