@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Contact Submission') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('contact-submissions.index') }}" class="btn btn-soft-secondary">
                {{ translate('Back to submissions') }}
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Submitted Message') }}</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ translate('Full Name') }}</dt>
                    <dd class="col-sm-8">{{ $submission->full_name }}</dd>

                    <dt class="col-sm-4">{{ translate('Email') }}</dt>
                    <dd class="col-sm-8"><a href="mailto:{{ $submission->email }}">{{ $submission->email }}</a></dd>

                    <dt class="col-sm-4">{{ translate('Phone') }}</dt>
                    <dd class="col-sm-8">{{ $submission->phone ?: '-' }}</dd>

                    <dt class="col-sm-4">{{ translate('Inquiry Type') }}</dt>
                    <dd class="col-sm-8">{{ $submission->inquiry_type ?: translate('General') }}</dd>

                    <dt class="col-sm-4">{{ translate('Source Page') }}</dt>
                    <dd class="col-sm-8">{{ $submission->source_page }}</dd>

                    <dt class="col-sm-4">{{ translate('Submitted') }}</dt>
                    <dd class="col-sm-8">{{ $submission->created_at?->format('d-m-Y H:i:s') }}</dd>

                    <dt class="col-sm-4">{{ translate('IP Address') }}</dt>
                    <dd class="col-sm-8">{{ $submission->ip_address ?: '-' }}</dd>

                    <dt class="col-sm-4">{{ translate('User Agent') }}</dt>
                    <dd class="col-sm-8 text-break">{{ $submission->user_agent ?: '-' }}</dd>
                </dl>

                <hr>
                <h6 class="fw-600">{{ translate('Message') }}</h6>
                <p class="mb-0" style="white-space: pre-wrap;">{{ $submission->message }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Admin Review') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('contact-submissions.update', $submission) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Status') }}</label>
                        <select class="form-control" name="status">
                            @foreach (\App\Models\ContactSubmission::STATUSES as $value => $label)
                                <option value="{{ $value }}" {{ $submission->status === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Admin Note') }}</label>
                        <textarea class="form-control" rows="8" name="admin_note">{{ old('admin_note', $submission->admin_note) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Save Review') }}</button>
                    <a href="#" class="btn btn-soft-danger confirm-delete" data-href="{{ route('contact-submissions.destroy', $submission) }}">
                        {{ translate('Delete') }}
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('backend.inc.delete_modal')
@endsection
