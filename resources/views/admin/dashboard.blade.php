@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <div class="info-box-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="info-box-content">
                    <h2>Painel Administrativo</h2>
                    <p>Bem-vindo ao sistema de monitoramento de ônibus</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="info-box info-box-gradient-primary">
                <div class="info-box-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <div class="info-box-content">
                    <div class="info-box-text">Trips Cadastradas</div>
                    <div class="info-box-number">{{ number_format($trips) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-box info-box-gradient-success">
                <div class="info-box-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="info-box-content">
                    <div class="info-box-text">Usuários Cadastrados</div>
                    <div class="info-box-number">{{ number_format($users) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-box info-box-gradient-warning">
                <div class="info-box-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="info-box-content">
                    <div class="info-box-text">Ônibus Cadastrados</div>
                    <div class="info-box-number">{{ number_format($buses) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <h3><i class="fas fa-cog"></i> Gerenciamento</h3>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route('admin.trips.index') }}" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-bus"></i><br>
                            Gerenciar Trips
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-info btn-block btn-lg">
                            <i class="fas fa-users"></i><br>
                            Gerenciar Usuários
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.buses.index') }}" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-truck"></i><br>
                            Gerenciar Ônibus
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.routes.index') }}" class="btn btn-warning btn-block btn-lg">
                            <i class="fas fa-map"></i><br>
                            Gerenciar Rotas
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary btn-block btn-lg">
                            <i class="fas fa-bell"></i><br>
                            Logs de Alertas
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.trip-alerts-logs.stats') }}" class="btn btn-dark btn-block btn-lg">
                            <i class="fas fa-chart-bar"></i><br>
                            Estatísticas de Alertas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Trips Ativas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="info-box">
                <h3><i class="fas fa-clock"></i> Últimas Trips</h3>
                <hr>
                @php
                    $recentTrips = \App\Models\Trip::with(['route', 'bus'])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                @endphp

                @if ($recentTrips->count() > 0)
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Rota</th>
                                    <th>Ônibus</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTrips as $trip)
                                    <tr>
                                        <td>#{{ $trip->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($trip->trip_date)->format('d/m/Y') }}</td>
                                        <td>{{ $trip->route->name ?? 'N/A' }}</td>
                                        <td>{{ $trip->bus->plate ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match ($trip->status) {
                                                    'in_progress' => 'badge-success',
                                                    'finished' => 'badge-info',
                                                    'cancelled' => 'badge-danger',
                                                    default => 'badge-secondary',
                                                };
                                                $statusLabel = match ($trip->status) {
                                                    'in_progress' => 'Em andamento',
                                                    'finished' => 'Finalizada',
                                                    'cancelled' => 'Cancelada',
                                                    default => 'Agendada',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trips.edit', $trip->id) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhuma trip cadastrada.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
