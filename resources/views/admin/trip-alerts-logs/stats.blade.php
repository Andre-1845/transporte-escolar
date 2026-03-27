@extends('layouts.admin')

@section('title', 'Estatísticas de Alertas')

@section('content')
<div class="admin-container">

    <!-- HEADER -->
    <div class="info-box">
        <div class="info-box-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="info-box-content d-flex-between">
            <h2>Estatísticas de Alertas</h2>
            <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- FILTRO -->
    <div class="info-box">
        <h3>Filtrar por Viagem</h3>
        <form method="GET">
            <div class="row">
                <div class="col-md-4">
                    <select name="trip_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Todas as viagens</option>
                        @foreach($trips as $trip)
                        <option value="{{ $trip->id }}" {{ request('trip_id') == $trip->id ? 'selected' : '' }}>
                            #{{ $trip->id }} - {{ $trip->trip_date }} ({{ $trip->route->name ?? 'N/A' }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- CARDS -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box gradient-primary">
                <div class="info-box-icon"><i class="fas fa-bell"></i></div>
                <div>
                    <div class="info-box-text">Total</div>
                    <div class="info-box-number">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box gradient-success">
                <div class="info-box-icon"><i class="fas fa-check"></i></div>
                <div>
                    <div class="info-box-text">Entregues</div>
                    <div class="info-box-number">{{ number_format($stats['delivery_rate']['delivered']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box gradient-danger">
                <div class="info-box-icon"><i class="fas fa-times"></i></div>
                <div>
                    <div class="info-box-text">Falhas</div>
                    <div class="info-box-number">{{ number_format($stats['delivery_rate']['failed']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box gradient-warning">
                <div class="info-box-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="info-box-text">Sucesso</div>
                    <div class="info-box-number">
                        {{ $stats['delivery_rate']['total'] > 0
                            ? number_format(($stats['delivery_rate']['delivered'] / $stats['delivery_rate']['total']) * 100, 1)
                            : 0 }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABELA TIPOS -->
    <div class="info-box">
        <h3>Alertas por Tipo</h3>

        @php $totalAlertas = $stats['by_type']->sum('total'); @endphp

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Qtd</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['by_type'] as $item)
                    @php
                    $map = [
                    'approaching' => 'Aproximação',
                    'reached' => 'Chegada',
                    'passed' => 'Passagem',
                    'end_warning' => 'Fim da Rota',
                    'broadcast' => 'Broadcast',
                    'driver_alert' => 'Motorista',
                    'student_alert' => 'Aluno'
                    ];
                    $nome = $map[$item->alert_type] ?? $item->alert_type;
                    $percentual = $totalAlertas > 0 ? ($item->total / $totalAlertas) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $nome }}</td>
                        <td>{{ number_format($item->total) }}</td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ number_format($percentual,2) }} %">
                                    {{ number_format($percentual,1) }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
