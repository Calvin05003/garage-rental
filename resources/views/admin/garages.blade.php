@extends('layouts.app')

@section('title', 'Garages')

@section('content')
    <h1>Garages</h1>
    <h2>Create garages</h2>
    <form method="POST" action="{{ route('admin.garages.store') }}" class="auth-form">
        @csrf
        <div class="field">
            <label for="garage_type_id">Garage type</label>
            <select id="garage_type_id" name="garage_type_id" data-garage-type required aria-invalid="{{ $errors->has('garage_type_id') ? 'true' : 'false' }}" @error('garage_type_id') aria-describedby="garage-type-error" @enderror>
                <option value="">Select a garage type</option>
                @foreach($garageTypes as $type)
                    <option value="{{ $type->garage_type_id }}" data-prefix="{{ $type->prefix }}" @selected((string) old('garage_type_id') === (string) $type->garage_type_id)>{{ $type->displayName() }}</option>
                @endforeach
            </select>
            @error('garage_type_id')<p class="field-error" id="garage-type-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="quantity">Quantity</label>
            <input id="quantity" name="quantity" type="number" value="{{ old('quantity', 1) }}" min="1" max="999" step="1" required aria-invalid="{{ $errors->has('quantity') ? 'true' : 'false' }}" @error('quantity') aria-describedby="quantity-error" @enderror>
            @error('quantity')<p class="field-error" id="quantity-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="daily_rate">Daily rate</label>
            <div class="currency-input">
                <span id="daily-rate-currency">Rp</span>
                <input id="daily_rate" name="daily_rate" type="text" value="{{ old('daily_rate') }}" placeholder="100.000" inputmode="decimal" data-currency required aria-invalid="{{ $errors->has('daily_rate') ? 'true' : 'false' }}" aria-describedby="daily-rate-currency{{ $errors->has('daily_rate') ? ' daily-rate-error' : '' }}">
            </div>
            @error('daily_rate')<p class="field-error" id="daily-rate-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field" data-electricity-field>
            <label for="electricity_rate">Electricity price per kWh</label>
            <div class="currency-input">
                <span id="electricity-rate-currency">Rp</span>
                <input id="electricity_rate" name="electricity_rate" type="text" value="{{ old('electricity_rate') }}" placeholder="2.500" inputmode="decimal" data-currency aria-invalid="{{ $errors->has('electricity_rate') ? 'true' : 'false' }}" aria-describedby="electricity-rate-currency{{ $errors->has('electricity_rate') ? ' electricity-rate-error' : '' }}">
            </div>
            @error('electricity_rate')<p class="field-error" id="electricity-rate-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit">Create garages</button>
    </form>

    <h2>Garage list</h2>
    @if($garages->isEmpty())
        <p>No garages yet.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Code</th><th scope="col">Type</th><th scope="col">Daily rate</th><th scope="col">Electricity price per kWh</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @foreach($garages as $garage)
                        <tr>
                            <td>{{ $garage->code }}</td>
                            <td>{{ $garage->garageType->displayName() }}</td>
                            <td>Rp {{ number_format((float) $garage->daily_rate, 2, ',', '.') }}</td>
                            <td>{{ $garage->electricity_rate === null ? '—' : 'Rp '.number_format((float) $garage->electricity_rate, 2, ',', '.') }}</td>
                            <td>{{ $garage->status === 'available' ? 'Available' : 'Used' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($garages->hasPages())
            <nav class="pagination" aria-label="Garage pages">
                <span>{{ $garages->firstItem() }}–{{ $garages->lastItem() }} of {{ $garages->total() }} garages</span>
                @if($garages->previousPageUrl())<a href="{{ $garages->previousPageUrl() }}" rel="prev">Previous</a>@endif
                @if($garages->nextPageUrl())<a href="{{ $garages->nextPageUrl() }}" rel="next">Next</a>@endif
            </nav>
        @endif
    @endif
@endsection
