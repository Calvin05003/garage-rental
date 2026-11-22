@extends('layouts.app')

@section('title', 'Tenant dashboard')

@section('content')
    <h1>Tenant dashboard</h1>
    <p>Welcome, {{ $tenant->name }}.</p>
    <h2>Registration status</h2>
    @if($tenant->isApproved())
        <p class="status-message">Your account is approved.</p>
        <p><a href="{{ route('tenant.rentals.create') }}">New booking</a></p>
        <p><a href="{{ route('tenant.rentals.index') }}">My bookings</a></p>
    @elseif($tenant->verification_status === 'rejected')
        <p class="status-message">Your registration was rejected.</p>
        <p>Your account cannot be used to rent a garage. Contact the administrator for details.</p>
    @else
        <p class="status-message">Your registration is awaiting approval.</p>
    @endif
    <h2>Tenant details</h2>
    <dl>
        <dt>Full name</dt><dd>{{ $tenant->name }}</dd>
        <dt>NIK</dt><dd>{{ $tenant->nik ?? '—' }}</dd>
        <dt>Email</dt><dd>{{ $tenant->email }}</dd>
        <dt>Phone number</dt><dd>{{ $tenant->phone ?? '—' }}</dd>
    </dl>
@endsection
