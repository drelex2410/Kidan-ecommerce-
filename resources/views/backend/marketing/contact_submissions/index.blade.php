@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Contact Submissions') }}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Messages') }}</h5>
        <div class="pull-right clearfix">
            <form id="sort_contact_submissions" action="" method="GET">
                <div class="box-inline pad-rgt pull-left">
                    <select class="form-control" name="status" onchange="this.form.submit()">
                        <option value="">{{ translate('All Statuses') }}</option>
                        @foreach (\App\Models\ContactSubmission::STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="box-inline pad-rgt pull-left">
                    <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="{{ translate('Search messages') }}">
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('Email') }}</th>
                    <th data-breakpoints="lg">{{ translate('Inquiry Type') }}</th>
                    <th data-breakpoints="lg">{{ translate('Message') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th data-breakpoints="lg">{{ translate('Submitted') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $key => $submission)
                    <tr>
                        <td>{{ ($key + 1) + ($submissions->currentPage() - 1) * $submissions->perPage() }}</td>
                        <td>{{ $submission->full_name }}</td>
                        <td>{{ $submission->email }}</td>
                        <td>{{ $submission->inquiry_type ?: translate('General') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($submission->message, 90) }}</td>
                        <td>
                            <span class="badge badge-inline badge-{{ $submission->status === 'responded' ? 'success' : ($submission->status === 'read' ? 'info' : 'warning') }}">
                                {{ translate(\App\Models\ContactSubmission::STATUSES[$submission->status] ?? ucfirst($submission->status)) }}
                            </span>
                        </td>
                        <td>{{ $submission->created_at?->format('d-m-Y H:i') }}</td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('contact-submissions.show', $submission) }}" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('contact-submissions.destroy', $submission) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="clearfix">
            <div class="pull-right">
                {{ $submissions->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('backend.inc.delete_modal')
@endsection
