@extends('layouts.app')

@section('title', 'Tenant verification')

@section('content')
    <h1>Tenant verification</h1>
    <nav class="filter-tabs" aria-label="Registration status">
        <a href="{{ route('admin.tenants.index', ['status' => 'pending']) }}" aria-current="{{ $status === 'pending' ? 'page' : 'false' }}">Pending ({{ $counts['pending'] ?? 0 }})</a>
        <a href="{{ route('admin.tenants.index', ['status' => 'approved']) }}" aria-current="{{ $status === 'approved' ? 'page' : 'false' }}">Approved ({{ $counts['approved'] ?? 0 }})</a>
        <a href="{{ route('admin.tenants.index', ['status' => 'rejected']) }}" aria-current="{{ $status === 'rejected' ? 'page' : 'false' }}">Rejected ({{ $counts['rejected'] ?? 0 }})</a>
    </nav>
    @if($tenants->isEmpty())
        <p>No {{ $status }} registrations.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">NIK</th><th scope="col">Phone</th><th scope="col">Registered</th><th scope="col">{{ $status === 'pending' ? 'Decision' : 'Status' }}</th></tr></thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        <tr>
                            <td>{{ $tenant->name }}</td><td>{{ $tenant->email }}</td><td>{{ $tenant->nik }}</td><td>{{ $tenant->phone }}</td><td>{{ $tenant->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($status === 'pending')
                                    <div class="row-actions">
                                        <form method="POST" action="{{ route('admin.tenants.verify', $tenant) }}">@csrf @method('PATCH')<input type="hidden" name="verification_status" value="approved"><button type="submit" aria-label="Approve registration for {{ $tenant->name }}">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.tenants.verify', $tenant) }}">@csrf @method('PATCH')<input type="hidden" name="verification_status" value="rejected"><button type="submit" aria-label="Reject registration for {{ $tenant->name }}">Reject</button></form>
                                    </div>
                                @else
                                    {{ $status === 'approved' ? 'Approved' : 'Rejected' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
            <nav class="pagination" aria-label="Registration pages">
                <span>{{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }} tenants</span>
                @if($tenants->previousPageUrl())<a href="{{ $tenants->previousPageUrl() }}" rel="prev">Previous</a>@endif
                @if($tenants->nextPageUrl())<a href="{{ $tenants->nextPageUrl() }}" rel="next">Next</a>@endif
            </nav>
        @endif
    @endif
@endsection
