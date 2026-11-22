@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <h1>New booking</h1>
    @error('booking_quote')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    <form method="POST" action="{{ route('tenant.rentals.quote') }}" class="auth-form" data-new-booking data-availability-url="{{ route('tenant.garages.available') }}">
        @csrf
        <h2>Vehicle</h2>
        <div class="field">
            <label for="vehicle_type">Vehicle type</label>
            <select id="vehicle_type" name="vehicle_type" required aria-invalid="{{ $errors->has('vehicle_type') ? 'true' : 'false' }}" @error('vehicle_type') aria-describedby="vehicle-type-error" @enderror>
                <option value="">Select a vehicle type</option>
                @foreach(['sepeda' => 'Bicycle', 'sepeda_motor' => 'Motorcycle', 'mobil' => 'Car', 'mobil_listrik' => 'Electric car'] as $value => $label)
                    <option value="{{ $value }}" @selected(($form['vehicle_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('vehicle_type')<p class="field-error" id="vehicle-type-error" role="alert">{{ $message }}</p>@enderror
        </div>
        @foreach(['plate_number' => ['Plate number', 20], 'brand' => ['Brand', 100], 'model' => ['Model', 100]] as $name => [$label, $length])
            <div class="field" @if($name === 'plate_number') data-plate-field @endif>
                <label for="{{ $name }}">{{ $label }}</label>
                <input id="{{ $name }}" name="{{ $name }}" type="text" value="{{ $form[$name] ?? '' }}" maxlength="{{ $length }}" @required($name !== 'plate_number' || ($form['vehicle_type'] ?? '') !== 'sepeda') aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if($errors->has($name)) aria-describedby="{{ $name }}-error" @endif>
                @if($errors->has($name))<p class="field-error" id="{{ $name }}-error" role="alert">{{ $errors->first($name) }}</p>@endif
            </div>
        @endforeach
        <div class="field" data-battery-field>
            <label for="battery_capacity_kwh">Maximum battery capacity (kWh)</label>
            <input id="battery_capacity_kwh" name="battery_capacity_kwh" type="number" value="{{ $form['battery_capacity_kwh'] ?? '' }}" min="0.001" max="9999999.999" step="0.001" aria-invalid="{{ $errors->has('battery_capacity_kwh') ? 'true' : 'false' }}" @error('battery_capacity_kwh') aria-describedby="battery-error" @enderror>
            @error('battery_capacity_kwh')<p class="field-error" id="battery-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <h2>Rental</h2>
        <div class="field">
            <label for="booking_type">Rental type</label>
            <select id="booking_type" name="booking_type" required aria-invalid="{{ $errors->has('booking_type') ? 'true' : 'false' }}" @error('booking_type') aria-describedby="booking-type-error" @enderror>
                <option value="daily" @selected(($form['booking_type'] ?? 'daily') === 'daily')>Daily</option>
                <option value="monthly" @selected(($form['booking_type'] ?? '') === 'monthly')>Monthly</option>
            </select>
            @error('booking_type')<p class="field-error" id="booking-type-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="start_date">Start date</label>
            <input id="start_date" name="start_date" type="date" value="{{ $form['start_date'] }}" min="{{ today()->toDateString() }}" max="9999-12-30" required aria-invalid="{{ $errors->has('start_date') ? 'true' : 'false' }}" @error('start_date') aria-describedby="start-date-error" @enderror>
            @error('start_date')<p class="field-error" id="start-date-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="end_date">End date</label>
            <input id="end_date" name="end_date" type="date" value="{{ $form['end_date'] }}" max="9999-12-31" required aria-invalid="{{ $errors->has('end_date') ? 'true' : 'false' }}" @error('end_date') aria-describedby="end-date-error" @enderror>
            @error('end_date')<p class="field-error" id="end-date-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <p data-booking-duration role="status"></p>
        <div class="field">
            <label for="garage_type_id">Garage type</label>
            <select id="garage_type_id" name="garage_type_id" required aria-invalid="{{ $errors->has('garage_type_id') ? 'true' : 'false' }}" @error('garage_type_id') aria-describedby="garage-type-error" @enderror>
                <option value="">Select a garage type</option>
                @foreach($garageTypes as $type)
                    <option value="{{ $type->garage_type_id }}" data-prefix="{{ $type->prefix }}" @selected((string) ($form['garage_type_id'] ?? '') === (string) $type->garage_type_id)>{{ $type->displayName() }}</option>
                @endforeach
            </select>
            @error('garage_type_id')<p class="field-error" id="garage-type-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="garage_id">Garage</label>
            <select id="garage_id" name="garage_id" required aria-invalid="{{ $errors->has('garage_id') ? 'true' : 'false' }}" aria-describedby="availability-status{{ $errors->has('garage_id') ? ' garage-error' : '' }}">
                <option value="">Select a garage</option>
                @foreach($garages as $garage)
                    <option value="{{ $garage->garage_id }}" @selected((string) ($form['garage_id'] ?? '') === (string) $garage->garage_id)>{{ $garage->code }} — Rp {{ number_format((float) $garage->daily_rate, 2, ',', '.') }} / day</option>
                @endforeach
            </select>
            <p class="field-hint" id="availability-status" data-availability-status role="status">Choose a garage type to see available garages.</p>
            @error('garage_id')<p class="field-error" id="garage-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field" data-charge-field>
            <label for="initial_charge_percent">Current charge (%)</label>
            <input id="initial_charge_percent" name="initial_charge_percent" type="number" value="{{ $form['initial_charge_percent'] ?? '' }}" min="0" max="100" step="0.01" aria-invalid="{{ $errors->has('initial_charge_percent') ? 'true' : 'false' }}" @error('initial_charge_percent') aria-describedby="charge-error" @enderror>
            @error('initial_charge_percent')<p class="field-error" id="charge-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit">Calculate fee</button>
    </form>

    @if($quote !== null)
        <section data-booking-quote>
            <h2>Booking summary</h2>
            <dl>
                <dt>Garage</dt><dd>{{ $quote['garage_code'] }} ({{ $quote['garage_type'] }})</dd>
                <dt>Vehicle</dt><dd>{{ $quote['brand'] }} {{ $quote['model'] }}{{ isset($quote['plate_number']) ? ' ('.$quote['plate_number'].')' : '' }}</dd>
                <dt>Rental type</dt><dd>{{ $quote['booking_type'] === 'monthly' ? 'Monthly' : 'Daily' }}</dd>
                <dt>Start date</dt><dd>{{ \Carbon\CarbonImmutable::parse($quote['start_date'])->format('d/m/Y') }}</dd>
                <dt>End date</dt><dd>{{ \Carbon\CarbonImmutable::parse($quote['end_date'])->format('d/m/Y') }}</dd>
                <dt>Duration</dt><dd>{{ $quote['duration_days'] }} {{ $quote['duration_days'] === 1 ? 'day' : 'days' }}</dd>
                <dt>Rental fee</dt><dd>Rp {{ number_format((float) $quote['rental_amount'], 2, ',', '.') }}</dd>
                @if($quote['booked_electricity_rate'] !== null)
                    <dt>Charging to full</dt><dd>{{ number_format((float) $quote['electricity_kwh'], 3, ',', '.') }} kWh</dd>
                    <dt>Electricity price per kWh</dt><dd>Rp {{ number_format((float) $quote['booked_electricity_rate'], 2, ',', '.') }}</dd>
                    <dt>Charging fee</dt><dd>Rp {{ number_format((float) $quote['electricity_amount'], 2, ',', '.') }}</dd>
                @endif
                <dt>Total payment</dt><dd><strong>Rp {{ number_format((float) $quote['total_amount'], 2, ',', '.') }}</strong></dd>
            </dl>
            <form method="POST" action="{{ route('tenant.rentals.store') }}" enctype="multipart/form-data" class="auth-form">
                @csrf
                <input type="hidden" name="booking_quote" value="{{ $bookingToken }}">
                <div class="field">
                    <label for="payment_receipt">Payment receipt</label>
                    <input id="payment_receipt" name="payment_receipt" type="file" accept="image/jpeg,image/png" required aria-invalid="{{ $errors->has('payment_receipt') ? 'true' : 'false' }}" aria-describedby="receipt-hint{{ $errors->has('payment_receipt') ? ' receipt-error' : '' }}">
                    <p class="field-hint" id="receipt-hint">JPG or PNG. Maximum 5 MB.</p>
                    @error('payment_receipt')<p class="field-error" id="receipt-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit">Book garage</button>
            </form>
        </section>
    @endif
@endsection
