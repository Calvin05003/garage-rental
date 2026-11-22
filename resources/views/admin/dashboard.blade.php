@extends('layouts.app')

@section('title', 'Admin dashboard')

@section('content')
    <h1>Admin dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}.</p>
    <ul>
        <li>Total garages: {{ $garageCount }}</li>
        <li>Total tenants: {{ $tenantCount }}</li>
        <li>Awaiting approval: {{ $pendingCount }}</li>
    </ul>
    <p><a href="{{ route('admin.garages.index') }}">Manage garages</a></p>
    <p><a href="{{ route('admin.rentals.index') }}">View current bookings</a></p>
    <p><a href="{{ route('admin.reports.index') }}">View rental reports</a></p>
    <h2>New registrations</h2>
    @if($pendingTenants->isEmpty())
        <p>No new registrations.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">NIK</th><th scope="col">Registered</th></tr></thead>
                <tbody>
                    @foreach($pendingTenants as $tenant)
                        <tr><td>{{ $tenant->name }}</td><td>{{ $tenant->email }}</td><td>{{ $tenant->nik }}</td><td>{{ $tenant->created_at->format('d/m/Y') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <p><a href="{{ route('admin.tenants.index') }}">View tenant registrations</a></p>
@endsection
