@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <h1>Log in</h1>
    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required autofocus aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
            @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="password-error" @enderror>
            @error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit">Log in</button>
    </form>
    <p>Don't have an account? <a href="{{ route('register') }}">Register</a></p>
@endsection
