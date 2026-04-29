@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Refund Policies') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @can('add_refund_policies')
                    <a href="{{ route('admin.refund_policies.create') }}" class="btn btn-circle btn-primary">
                        <span>{{ translate('Add New Policy') }}</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header row gutters-5 align-items-center">
            <div class="col">
                <h5 class="mb-md-0 h6">{{ translate('Refund Policy List') }}</h5>
            </div>
            <div class="col-md-2">
                <form id="sort_refund_policies" action="" method="GET">
                    @if ($sort_search)
                        <input type="hidden" name="search" value="{{ $sort_search }}">
                    @endif
                    <select name="status" class="form-control aiz-selectpicker" onchange="document.getElementById('sort_refund_policies').submit();">
                        <option value="">{{ translate('All Statuses') }}</option>
                        <option value="1" @selected((string) $status === '1')>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) $status === '0')>{{ translate('Inactive') }}</option>
                    </select>
                </form>
            </div>
            <div class="col-md-3">
                <form id="search_refund_policies" action="" method="GET">
                    @if ($status !== null && $status !== '')
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search"
                            value="{{ $sort_search }}" placeholder="{{ translate('Type name/code & hit Enter') }}">
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Policy') }}</th>
                        <th>{{ translate('Window') }}</th>
                        <th>{{ translate('Order Statuses') }}</th>
                        <th>{{ translate('Refund Method') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refundPolicies as $key => $refundPolicy)
                        <tr>
                            <td>{{ $key + 1 + ($refundPolicies->currentPage() - 1) * $refundPolicies->perPage() }}</td>
                            <td>
                                <div class="fw-600">{{ $refundPolicy->name }}</div>
                                <div class="small text-muted">{{ $refundPolicy->code ?: '—' }}</div>
                            </td>
                            <td>{{ $refundPolicy->refund_window_days }} {{ translate('days') }}</td>
                            <td>
                                @php
                                    $statuses = collect($refundPolicy->allowed_order_statuses ?? [])
                                        ->map(fn ($statusValue) => translate(ucwords(str_replace('_', ' ', $statusValue))))
                                        ->implode(', ');
                                @endphp
                                <span>{{ $statuses !== '' ? $statuses : translate('Not configured') }}</span>
                            </td>
                            <td>{{ translate(ucwords(str_replace('_', ' ', $refundPolicy->refund_method_type ?? 'manual'))) }}</td>
                            <td>
                                @can('edit_refund_policies')
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="update_status(this)" value="{{ $refundPolicy->id }}"
                                            @checked($refundPolicy->is_active)>
                                        <span></span>
                                    </label>
                                @else
                                    <span class="badge badge-inline {{ $refundPolicy->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $refundPolicy->is_active ? translate('Active') : translate('Inactive') }}
                                    </span>
                                @endcan
                            </td>
                            <td class="text-right">
                                @can('edit_refund_policies')
                                    <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                        href="{{ route('admin.refund_policies.edit', $refundPolicy->id) }}"
                                        title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ translate('No refund policies found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $refundPolicies->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function update_status(el) {
            let status = el.checked ? 1 : 0;

            $.post('{{ route('admin.refund_policies.update_status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Refund policy status updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }
    </script>
@endsection
