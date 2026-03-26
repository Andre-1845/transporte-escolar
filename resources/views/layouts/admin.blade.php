<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title', 'Dashboard')</title>

    <!-- Font Awesome 6 (ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js CDN (para gráficos) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- CSS GERAL DO ADMIN -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    @stack('styles')
</head>

<body>
    <!-- Navbar -->
    <nav class="admin-navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-bus"></i>
                <span>Admin Panel</span>
            </div>
            <div class="nav-links">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="{{ route('admin.trips.index') }}">
                    <i class="fas fa-bus"></i> Trips
                </a>
                <a href="{{ route('admin.users.index') }}">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="{{ route('admin.buses.index') }}">
                    <i class="fas fa-truck"></i> Buses
                </a>
                <a href="{{ route('admin.routes.index') }}">
                    <i class="fas fa-map"></i> Routes
                </a>
                <a href="{{ route('admin.trip-alerts-logs.index') }}">
                    <i class="fas fa-bell"></i> Logs
                </a>
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <!-- Mensagens Flash -->
        @if (session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </div>

    <!-- jQuery (opcional, para alguns componentes) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @stack('scripts')
</body>

</html>
