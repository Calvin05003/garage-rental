@extends('layouts.app')

@section('title', 'Rental reports')

@section('content')
    <h1>Rental reports</h1>
    <nav class="filter-tabs" aria-label="Report types">
        @foreach(['monthly' => 'Monthly', 'daily' => 'Daily', 'garage_type' => 'By garage type', 'tenant' => 'By tenant'] as $value => $label)
            <a href="{{ route('admin.reports.index', ['report' => $value, ...($value === 'monthly' ? ['year' => $year] : ($value === 'daily' ? ['year' => $year, 'month' => $month] : []))]) }}" @if($report === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @error('report')<p class="field-error" role="alert">{{ $message }}</p>@enderror

    @if(in_array($report, ['monthly', 'daily'], true))
        <form method="GET" action="{{ route('admin.reports.index') }}" class="auth-form">
            <input type="hidden" name="report" value="{{ $report }}">
            <div class="field">
                <label for="year">Year</label>
                <input id="year" name="year" type="number" value="{{ is_scalar(old('year', $year)) ? old('year', $year) : '' }}" min="1000" max="9999" step="1" required aria-invalid="{{ $errors->has('year') ? 'true' : 'false' }}" @error('year') aria-describedby="year-error" @enderror>
                @error('year')<p class="field-error" id="year-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @if($report === 'daily')
                <div class="field">
                    <label for="month">Month</label>
                    <select id="month" name="month" required aria-invalid="{{ $errors->has('month') ? 'true' : 'false' }}" @error('month') aria-describedby="month-error" @enderror>
                        @foreach(range(1, 12) as $number)
                            <option value="{{ $number }}" @selected(is_scalar(old('month', $month)) && (string) old('month', $month) === (string) $number)>{{ \Carbon\CarbonImmutable::create(2000, $number, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                    @error('month')<p class="field-error" id="month-error" role="alert">{{ $message }}</p>@enderror
                </div>
            @endif
            <button type="submit">Show report</button>
        </form>
    @endif

    <h2>{{ $period }}</h2>
    @if(in_array($report, ['monthly', 'daily'], true))
        <p class="field-hint">New bookings use start dates. Payments use payment dates.</p>
    @endif
    @if($rows->isEmpty())
        <p>No rental data.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">{{ $groupLabel }}</th><th scope="col">New bookings</th><th scope="col">Payments</th><th scope="col">Amount received</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <th scope="row">{{ $row['label'] }}@if(isset($row['email']))<br><span>{{ $row['email'] }}</span>@endif</th>
                            <td>{{ $row['bookings'] }}</td>
                            <td>{{ $row['payments'] }}</td>
                            <td>{{ $formatAmount($row['amount_received']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th scope="row">Total</th><td>{{ $totals['bookings'] }}</td><td>{{ $totals['payments'] }}</td><td>{{ $formatAmount($totals['amount_received']) }}</td></tr></tfoot>
            </table>
        </div>
    @endif
@endsection
