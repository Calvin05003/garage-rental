<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Home') | Garage Rental</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}">Garage Rental</a>
        <nav aria-label="Main navigation">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.garages.index') }}">Garages</a>
                    <a href="{{ route('admin.rentals.index') }}">Current bookings</a>
                    <a href="{{ route('admin.tenants.index') }}">Tenant registrations</a>
                    <a href="{{ route('admin.reports.index') }}">Reports</a>
                @elseif(auth()->user()->canRent())
                    <a href="{{ route('tenant.rentals.create') }}">New booking</a>
                    <a href="{{ route('tenant.rentals.index') }}">My bookings</a>
                @endif
                <span>{{ auth()->user()->name }} ({{ auth()->user()->isAdmin() ? 'Admin' : 'Tenant' }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>
    <main>
        @if(session('status'))
            <p class="status-message" role="status">{{ session('status') }}</p>
        @endif
        <div class="card @guest card-auth @endguest">
            @yield('content')
        </div>
    </main>
</body>
</html>
