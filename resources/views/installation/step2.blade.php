@extends('backend.layouts.blank')
@section('content')
    <div class="container h-100 d-flex flex-column justify-content-center">
        <div class="row">
            <div class="col-xl-6 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <div class="mar-ver pad-btm text-center">
                            <img src="{{ static_asset('assets/img/logo.png') }}" class="mb-4">
                            <h1 class="h3">Continue Installation</h1>
                            <p>Continue to the database setup step for this standalone installation.</p>
                        </div>
                        <p class="text-muted font-13">
                        <form method="POST" action="{{ route('purchase.code') }}">
                            @csrf
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Continue</button>
                            </div>
                        </form>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
