@extends('layouts.admin')

@section('title', 'Trips')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="d-flex-between">
            <h2><i class="fas fa-bus"></i> Trips</h2>

            <a href="{{ route('admin.trips.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Trip
            </a>
        </div>
    </div>

    <!-- TABELA -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3>Lista de Trips</h3>
            </div>

            <div class="table-container mt-2">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rota</th>
                            <th>Motorista</th>
                            <th>Veículo</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($trips as $trip)
                            @php
                                $map = [
                                    'scheduled' => ['badge-secondary', 'Agendada'],
                                    'in_progress' => ['badge-success', 'Em andamento'],
                                    'finished' => ['badge-info', 'Finalizada'],
                                    'cancelled' => ['badge-danger', 'Cancelada'],
                                ];

                                [$badge, $label] = $map[$trip->status] ?? ['badge-secondary', $trip->status];
                            @endphp

                            <tr>

                                <td>#{{ $trip->id }}</td>

                                <td>{{ $trip->route?->name ?? '-' }}</td>

                                <td>{{ $trip->driver?->name ?? '-' }}</td>

                                <td>{{ $trip->bus?->plate ?? '-' }}</td>

                                <td>
                                    {{ \Carbon\Carbon::parse($trip->trip_date)->format('d/m/Y') }}
                                </td>

                                <td>{{ $trip->start_time }}</td>

                                <td>
                                    <span class="badge {{ $badge }}">
                                        {{ $label }}
                                    </span>
                                </td>

                                <td style="display:flex; gap:6px;">

                                    <!-- EDITAR -->
                                    <a href="{{ route('admin.trips.edit', $trip->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- INICIAR -->
                                    @if ($trip->status == 'scheduled')
                                        <form action="{{ url('/admin/trips/' . $trip->id . '/start') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- FINALIZAR -->
                                    @if ($trip->status == 'in_progress')
                                        <form action="{{ url('/admin/trips/' . $trip->id . '/finish') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        </form>
                                    @endif

                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
