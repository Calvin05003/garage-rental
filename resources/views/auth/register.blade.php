@extends('layouts.app')

@section('title', 'Tenant registration')

@section('content')
    <h1>Tenant registration</h1>
    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
        @csrf
        <div class="field">
            <label for="nik">NIK</label>
            <input id="nik" name="nik" type="text" value="{{ old('nik') }}" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" title="NIK must contain exactly 16 digits." data-nik required aria-invalid="{{ $errors->has('nik') ? 'true' : 'false' }}" aria-describedby="nik-hint{{ $errors->has('nik') ? ' nik-error' : '' }}">
            <p class="field-hint" id="nik-hint">16 digits, as shown on your KTP.</p>
            @error('nik')<p class="field-error" id="nik-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="150" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="name-error" @enderror>
            @error('name')<p class="field-error" id="name-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="16" required aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" @error('phone') aria-describedby="phone-error" @enderror>
            @error('phone')<p class="field-error" id="phone-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
            @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="password-error" @enderror>
            @error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        </div>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
