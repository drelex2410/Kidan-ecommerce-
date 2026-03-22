@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('Add Customer') }}</h1>
            </div>
            <div class="col-auto">
                <a href="{{ route('customers.index') }}" class="btn btn-soft-primary">
                    {{ translate('Back to Customers') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Customer Information') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Name') }}</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Email') }}</label>
                    <div class="col-md-9">
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                        <small class="form-text text-muted">{{ translate('Provide email or phone so the customer can log in.') }}</small>
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Phone') }}</label>
                    <div class="col-md-9">
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                        @error('phone')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Password') }}</label>
                    <div class="col-md-9">
                        <input type="password" name="password" class="form-control" required>
                        <small class="form-text text-muted">{{ translate('Use at least 8 characters with uppercase, lowercase, and a number.') }}</small>
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Confirm Password') }}</label>
                    <div class="col-md-9">
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                        {{ translate('Create Customer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
