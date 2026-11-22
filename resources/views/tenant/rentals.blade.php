@extends('layouts.app')

@section('title', 'My bookings')

@section('content')
    <h1>My bookings</h1>
    <p><a href="{{ route('tenant.rentals.create') }}">New booking</a></p>
    @if($rentals->isEmpty())
        <p>No bookings yet.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Garage</th><th scope="col">Vehicle</th><th scope="col">Rental type</th><th scope="col">Start date</th><th scope="col">End date</th><th scope="col">Rental fee</th><th scope="col">Charging fee</th><th scope="col">Status</th><th scope="col">Receipts</th></tr></thead>
                <tbody>
                    @foreach($rentals as $rental)
                        <tr>
                            <td>{{ $rental->garage->code }}</td>
                            <td>{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}{{ $rental->vehicle->plate_number ? ' ('.$rental->vehicle->plate_number.')' : '' }}</td>
                            <td>{{ $rental->booking_type === 'monthly' ? 'Monthly' : 'Daily' }}</td>
                            <td>{{ $rental->start_date->format('d/m/Y') }}</td>
                            <td>{{ $rental->end_date->format('d/m/Y') }}</td>
                            <td>Rp {{ number_format((float) $rental->rental_amount, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $rental->electricity_amount, 2, ',', '.') }}</td>
                            <td>{{ $rental->start_date->isAfter(today()) ? 'Upcoming' : ($rental->isActiveOn(today()) ? 'Current' : 'Ended') }}</td>
                            <td>
                                @foreach($rental->payments->sortBy('payment_id') as $payment)
                                    <p><a href="{{ route('tenant.payments.receipt', $payment) }}">{{ $payment->created_at->format('d/m/Y') }} — Rp {{ number_format((float) $payment->amount, 2, ',', '.') }}</a></p>
                                @endforeach
                            </td>
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
