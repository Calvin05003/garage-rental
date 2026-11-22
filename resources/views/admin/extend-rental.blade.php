@extends('layouts.app')

@section('title', 'Extend booking')

@section('content')
    <h1>Extend booking</h1>
    <dl>
        <dt>Garage</dt><dd>{{ $rental->garage->code }} ({{ $rental->garage->garageType->displayName() }})</dd>
        <dt>Tenant</dt><dd>{{ $rental->tenant->name }}</dd>
        <dt>Current end date</dt><dd>{{ $rental->end_date->format('d/m/Y') }}</dd>
        <dt>Daily rate</dt><dd>Rp {{ number_format((float) $rental->booked_daily_rate, 2, ',', '.') }}</dd>
    </dl>
    <form method="GET" action="{{ route('admin.rentals.edit', $rental) }}" class="auth-form">
        <div class="field">
            <label for="end_date">New end date</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $newEndDate) }}" min="{{ $rental->end_date->copy()->addDay()->toDateString() }}" max="9999-12-31" required aria-invalid="{{ $errors->has('end_date') ? 'true' : 'false' }}" @error('end_date') aria-describedby="end-date-error" @enderror>
            @error('end_date')<p class="field-error" id="end-date-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit">Calculate fee</button>
    </form>

    @if($quote !== null)
        <h2>Extension fee</h2>
        <p>Additional days: {{ $quote['additional_days'] }}</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Pricing month</th><th scope="col">Days</th><th scope="col">Rate</th><th scope="col">Amount</th></tr></thead>
                <tbody>
                    @foreach($quote['blocks'] as $block)
                        <tr><td>{{ $block['month'] }}</td><td>{{ $block['days'] }}</td><td>{{ $block['type'] }}</td><td>Rp {{ number_format((float) $block['amount'], 2, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p>Additional payment: <strong>Rp {{ number_format((float) $quote['amount'], 2, ',', '.') }}</strong></p>
        <p>New end date: {{ \Carbon\CarbonImmutable::parse($newEndDate)->format('d/m/Y') }}</p>
        <form method="POST" action="{{ route('admin.rentals.extend', $rental) }}" enctype="multipart/form-data" class="auth-form">
            @csrf
            <input type="hidden" name="end_date" value="{{ $newEndDate }}">
            <input type="hidden" name="expected_end_date" value="{{ $rental->end_date->toDateString() }}">
            @error('expected_end_date')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <div class="field">
                <label for="payment_receipt">Payment receipt</label>
                <input id="payment_receipt" name="payment_receipt" type="file" accept="image/jpeg,image/png" required aria-invalid="{{ $errors->has('payment_receipt') ? 'true' : 'false' }}" aria-describedby="receipt-hint{{ $errors->has('payment_receipt') ? ' receipt-error' : '' }}">
                <p class="field-hint" id="receipt-hint">JPG or PNG. Maximum 5 MB.</p>
                @error('payment_receipt')<p class="field-error" id="receipt-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit">Save extension</button>
        </form>
    @endif

    <h2>Payments</h2>
    @if($rental->payments->isEmpty())
        <p>No payments recorded.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Date</th><th scope="col">Amount</th><th scope="col">Receipt</th></tr></thead>
                <tbody>
                    @foreach($rental->payments->sortBy('payment_id') as $payment)
                        <tr><td>{{ $payment->created_at->format('d/m/Y H:i') }}</td><td>Rp {{ number_format((float) $payment->amount, 2, ',', '.') }}</td><td><a href="{{ route('admin.payments.receipt', $payment) }}">View receipt</a></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <p><a href="{{ route('admin.rentals.index') }}">Back to current bookings</a></p>
@endsection
