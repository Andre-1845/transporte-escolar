@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="info-box">
            <div class="info-box-icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>

            <div>
                <h2>Painel Administrativo</h2>
                <p>Monitoramento de ônibus em tempo real</p>
            </div>
        </div>
    </div>

    <!-- CARDS -->
    <div class="section">
        <div class="row">

            <div class="col-md-4">
                <div class="info-box gradient-primary">
                    <div class="info-box-icon"><i class="fas fa-bus"></i></div>
                    <div>
                        <div class="info-box-text">Trips</div>
                        <div class="info-box-number">{{ number_format($trips) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box gradient-success">
                    <div class="info-box-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="info-box-text">Usuários</div>
                        <div class="info-box-number">{{ number_format($users) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-box gradient-warning">
                    <div class="info-box-icon"><i class="fas fa-truck"></i></div>
                    <div>
                        <div class="info-box-text">Ônibus</div>
                        <div class="info-box-number">{{ number_format($buses) }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- GERENCIAMENTO -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header d-flex-between">
                <h3><i class="fas fa-cog"></i> Gerenciamento</h3>
            </div>

            <div class="row mt-2">

                <div class="col-md-3">
                    <a href="{{ route('admin.trips.index') }}" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-bus"></i>
                        Trips
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-info btn-block btn-lg">
                        <i class="fas fa-users"></i>
                        Usuários
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('admin.buses.index') }}" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-truck"></i>
                        Ônibus
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('admin.routes.index') }}" class="btn btn-warning btn-block btn-lg">
                        <i class="fas fa-map"></i>
                        Rotas
                    </a>
                </div>

            </div>

            <div class="row mt-2">

                <div class="col-md-3">
                    <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary btn-block btn-lg">
                        <i class="fas fa-bell"></i>
                        Logs
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('admin.trip-alerts-logs.stats') }}" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-chart-bar"></i>
                        Estatísticas
                    </a>
                </div>

            </div>

        </div>
    </div>

    <!-- ÚLTIMAS TRIPS -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3><i class="fas fa-clock"></i> Últimas Trips</h3>
            </div>

            @php
                $recentTrips = \App\Models\Trip::with(['route', 'bus'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            @endphp

            @if ($recentTrips->count() > 0)

                <div class="table-container mt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Rota</th>
                                <th>Ônibus</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($recentTrips as $trip)
                                @php
                                    $map = [
                                        'in_progress' => ['badge-success', 'Em andamento'],
                                        'finished' => ['badge-info', 'Finalizada'],
                                        'cancelled' => ['badge-danger', 'Cancelada'],
                                    ];

                                    [$badge, $label] = $map[$trip->status] ?? ['badge-secondary', 'Agendada'];
                                @endphp

                                <tr>
                                    <td>#{{ $trip->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($trip->trip_date)->format('d/m/Y') }}</td>
                                    <td>{{ $trip->route->name ?? '-' }}</td>
                                    <td>{{ $trip->bus->plate ?? '-' }}</td>

                                    <td>
                                        <span class="badge {{ $badge }}">{{ $label }}</span>
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.trips.edit', $trip->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mt-2">
                    Nenhuma trip cadastrada.
                </div>

            @endif

        </div>
    </div>

@endsection
