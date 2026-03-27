@extends('layouts.admin')

@section('title', 'Estatísticas de Alertas')

@section('content')
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-chart-bar text-primary"></i>
                    <h4 class="mb-0">Estatísticas de Alertas</h4>
                </div>

                <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list"></i> Ver Logs
                </a>
            </div>
        </div>

        <!-- FILTRO -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Filtrar por Viagem</label>
                            <select name="trip_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Todas as viagens</option>
                                @foreach ($trips as $trip)
                                    <option value="{{ $trip->id }}"
                                        {{ request('trip_id') == $trip->id ? 'selected' : '' }}>
                                        #{{ $trip->id }} - {{ $trip->trip_date }}
                                        ({{ $trip->route->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- CARDS -->
        <div class="row g-3 mb-3">

            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <small>Total de Alertas</small>
                        <h4 class="mb-0">{{ number_format($stats['total']) }}</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <small>Entregues</small>
                        <h4 class="mb-0">
                            {{ number_format($stats['delivery_rate']['delivered']) }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white bg-danger shadow-sm">
                    <div class="card-body">
                        <small>Falhas</small>
                        <h4 class="mb-0">
                            {{ number_format($stats['delivery_rate']['failed']) }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white bg-dark shadow-sm">
                    <div class="card-body">
                        <small>Taxa de Sucesso</small>
                        <h4 class="mb-0">
                            @if ($stats['delivery_rate']['total'] > 0)
                                {{ number_format(($stats['delivery_rate']['delivered'] / $stats['delivery_rate']['total']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </h4>
                    </div>
                </div>
            </div>

        </div>

        <!-- GRÁFICOS -->
        <div class="row g-3 mb-3">

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <strong>Alertas por Tipo</strong>
                    </div>
                    <div class="card-body">
                        <canvas id="alertTypesChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <strong>Últimos 30 Dias</strong>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- DETALHES -->
        @if (isset($stats['trip_details']))
            <div class="card">
                <div class="card-header">
                    <strong>Detalhes da Viagem #{{ $stats['trip_details']['trip']->id }}</strong>
                </div>

                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Data:</strong> {{ $stats['trip_details']['trip']->trip_date }}<br>
                            <strong>Horário:</strong> {{ $stats['trip_details']['trip']->start_time }}<br>

                            <strong>Status:</strong>
                            <span
                                class="badge
                            {{ $stats['trip_details']['trip']->status == 'in_progress'
                                ? 'bg-success'
                                : ($stats['trip_details']['trip']->status == 'finished'
                                    ? 'bg-info'
                                    : 'bg-secondary') }}">
                                {{ $stats['trip_details']['trip']->status }}
                            </span>
                        </div>

                        <div class="col-md-4">
                            <strong>Total Alertas:</strong> {{ $stats['trip_details']['alerts_count'] }}<br>
                            <strong>Alunos Alertados:</strong> {{ $stats['trip_details']['students_alerted'] }}<br>
                            <strong>Rota:</strong> {{ $stats['trip_details']['trip']->route->name ?? 'N/A' }}
                        </div>

                        <div class="col-md-4">
                            <strong>Ônibus:</strong> {{ $stats['trip_details']['trip']->bus->plate ?? 'N/A' }}<br>
                            <strong>Motorista:</strong> {{ $stats['trip_details']['trip']->driver->name ?? 'N/A' }}
                        </div>
                    </div>

                    @if ($stats['trip_details']['by_type']->count() > 0)

                        <h5 class="mb-2">Alertas por Tipo nesta Viagem</h5>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>%</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $totalTrip = $stats['trip_details']['alerts_count'];
                                        $alertLabels = [
                                            'approaching' => 'Aproximação',
                                            'reached' => 'Chegada',
                                            'passed' => 'Passagem',
                                            'end_warning' => 'Fim da Rota',
                                            'broadcast' => 'Broadcast',
                                        ];
                                    @endphp

                                    @foreach ($stats['trip_details']['by_type'] as $item)
                                        @php
                                            $label = $alertLabels[$item->alert_type] ?? $item->alert_type;
                                            $percent = $totalTrip > 0 ? ($item->total / $totalTrip) * 100 : 0;
                                        @endphp

                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>{{ number_format($item->total) }}</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: {{ $percent }}%">
                                                        {{ number_format($percent, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>

                    @endif

                </div>
            </div>
        @endif

    </div>
@endsection
