@extends('layouts.app')

@section('title', 'Current bookings')

@section('content')
    <h1>Current bookings</h1>
    @if($rentals->isEmpty())
        <p>No current bookings.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Garage</th><th scope="col">Type</th><th scope="col">Tenant</th><th scope="col">Vehicle</th><th scope="col">Start date</th><th scope="col">End date</th><th scope="col">Action</th></tr></thead>
                <tbody>
                    @foreach($rentals as $rental)
                        <tr>
                            <td>{{ $rental->garage->code }}</td>
                            <td>{{ $rental->garage->garageType->displayName() }}</td>
                            <td>{{ $rental->tenant->name }}</td>
                            <td>{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}{{ $rental->vehicle->plate_number ? ' ('.$rental->vehicle->plate_number.')' : '' }}</td>
                            <td>{{ $rental->start_date->format('d/m/Y') }}</td>
                            <td>{{ $rental->end_date->format('d/m/Y') }}</td>
                            <td><a href="{{ route('admin.rentals.edit', $rental) }}">Extend</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($rentals->hasPages())
            <nav class="pagination" aria-label="Booking pages">
                <span>{{ $rentals->firstItem() }}–{{ $rentals->lastItem() }} of {{ $rentals->total() }} bookings</span>
                @if($rentals->previousPageUrl())<a href="{{ $rentals->previousPageUrl() }}" rel="prev">Previous</a>@endif
                @if($rentals->nextPageUrl())<a href="{{ $rentals->nextPageUrl() }}" rel="next">Next</a>@endif
            </nav>
        @endif
    @endif
@endsection
