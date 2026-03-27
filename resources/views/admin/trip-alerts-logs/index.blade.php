@extends('layouts.admin')

@section('title', 'Logs de Alertas')

@section('content')
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-bell fa-lg text-primary"></i>
                    <h4 class="mb-0">Logs de Alertas</h4>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.trip-alerts-logs.stats', request()->query()) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-chart-bar"></i> Estatísticas
                    </a>

                    <a href="{{ route('admin.trip-alerts-logs.export', request()->query()) }}"
                        class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> Exportar CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET">
                    <div class="row g-2">

                        <div class="col-md-3">
                            <label class="form-label">Viagem</label>
                            <select name="trip_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach ($trips as $trip)
                                    <option value="{{ $trip->id }}"
                                        {{ request('trip_id') == $trip->id ? 'selected' : '' }}>
                                        #{{ $trip->id }} - {{ $trip->trip_date }}
                                        ({{ $trip->route->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tipo Alerta</label>
                            <select name="alert_type" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach ($alertTypes as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ request('alert_type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Data Início</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Data Fim</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Entregue?</label>
                            <select name="delivered" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1" {{ request('delivered') == '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ request('delivered') == '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>

                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Filtrar
                        </button>

                        <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eraser"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABELA -->
        <div class="card">
            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Viagem</th>
                                <th>Tipo</th>
                                <th>Stop</th>
                                <th>Usuário</th>
                                <th>Distância</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i') }}
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.trips.edit', $log->trip_id) }}">
                                            #{{ $log->trip_id }}
                                        </a>
                                    </td>

                                    <td>
                                        @php
                                            $badgeClass = match ($log->alert_type) {
                                                'approaching' => 'bg-warning',
                                                'reached' => 'bg-success',
                                                'passed' => 'bg-info',
                                                'end_warning' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };

                                            $alertLabels = [
                                                'approaching' => 'Aproximação',
                                                'reached' => 'Chegada',
                                                'passed' => 'Passagem',
                                                'end_warning' => 'Fim Rota',
                                                'broadcast' => 'Broadcast',
                                                'driver_alert' => 'Motorista',
                                                'student_alert' => 'Aluno',
                                            ];
                                        @endphp

                                        <span class="badge {{ $badgeClass }}">
                                            {{ $alertLabels[$log->alert_type] ?? $log->alert_type }}
                                        </span>
                                    </td>

                                    <td>{{ $log->stop->name ?? '-' }}</td>
                                    <td>{{ $log->user->name ?? '-' }}</td>

                                    <td>
                                        @if ($log->distance_at_alert)
                                            {{ $log->distance_at_alert < 1000
                                                ? round($log->distance_at_alert) . 'm'
                                                : number_format($log->distance_at_alert / 1000, 1) . 'km' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $log->delivered ? 'bg-success' : 'bg-danger' }}">
                                            {{ $log->delivered ? 'Entregue' : 'Falhou' }}
                                        </span>
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.trip-alerts-logs.show', $log->id) }}"
                                            class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3">
                                        Nenhum registro encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- PAGINAÇÃO -->
            @if (method_exists($logs, 'links'))
                <div class="card-footer">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
