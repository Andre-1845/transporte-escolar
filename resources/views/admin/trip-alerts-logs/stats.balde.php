@extends('layouts.admin')

@section('title', 'Estatísticas de Alertas')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="info-box">
            <div class="info-box-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="info-box-content">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Estatísticas de Alertas</h2>
                    <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar para Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtro por Viagem -->
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

        <!-- Cards de Resumo -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="info-box-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="info-box-content">
                        <div class="info-box-text">Total de Alertas</div>
                        <div class="info-box-number">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box"
                    style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                    <div class="info-box-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="info-box-content">
                        <div class="info-box-text">Entregues</div>
                        <div class="info-box-number">{{ number_format($stats['delivery_rate']['delivered']) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box"
                    style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); color: white;">
                    <div class="info-box-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="info-box-content">
                        <div class="info-box-text">Falhas</div>
                        <div class="info-box-number">{{ number_format($stats['delivery_rate']['failed']) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box"
                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <div class="info-box-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="info-box-content">
                        <div class="info-box-text">Taxa de Sucesso</div>
                        <div class="info-box-number">
                            @if($stats['delivery_rate']['total'] > 0)
                            {{ number_format(($stats['delivery_rate']['delivered'] / $stats['delivery_rate']['total']) * 100, 1) }}%
                            @else
                            0%
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas por Tipo -->
        <div class="info-box">
            <h3>Alertas por Tipo</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Percentual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $totalAlertas = $stats['by_type']->sum('total');
                        @endphp

                        @foreach($stats['by_type'] as $item)
                        @php
                        // Definindo labels sem match()
                        $nomeTipo = '';
                        switch($item->alert_type) {
                        case 'approaching':
                        $nomeTipo = 'Aproximação';
                        break;
                        case 'reached':
                        $nomeTipo = 'Chegada';
                        break;
                        case 'passed':
                        $nomeTipo = 'Passagem';
                        break;
                        case 'end_warning':
                        $nomeTipo = 'Fim da Rota';
                        break;
                        case 'broadcast':
                        $nomeTipo = 'Broadcast';
                        break;
                        case 'driver_alert':
                        $nomeTipo = 'Alerta Motorista';
                        break;
                        case 'student_alert':
                        $nomeTipo = 'Alerta Aluno';
                        break;
                        default:
                        $nomeTipo = $item->alert_type;
                        }
                        $percentual = $totalAlertas > 0 ? ($item->total / $totalAlertas) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $nomeTipo }}</td>
                            <td>{{ number_format($item->total) }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {{ $percentual }}%">
                                        {{ number_format($percentual, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Últimos 30 Dias -->
        <div class="info-box">
            <h3>Últimos 30 Dias</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Alertas</th>
                            <th>Tendência</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $maxAlertas = $stats['by_day']->max('total');
                        @endphp

                        @foreach($stats['by_day'] as $item)
                        @php
                        $dataFormatada = \Carbon\Carbon::parse($item->date)->format('d/m/Y');
                        $larguraBarra = $maxAlertas > 0 ? ($item->total / $maxAlertas) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $dataFormatada }}</td>
                            <td>{{ number_format($item->total) }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: {{ $larguraBarra }}%">
                                        {{ $item->total }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detalhes da Viagem (se filtrada) -->
        @if(isset($stats['trip_details']))
        <div class="info-box">
            <h3>Detalhes da Viagem #{{ $stats['trip_details']['trip']->id }}</h3>
            <div class="row">
                <div class="col-md-4">
                    <strong>Data:</strong> {{ $stats['trip_details']['trip']->trip_date }}<br>
                    <strong>Horário:</strong> {{ $stats['trip_details']['trip']->start_time }}<br>
                    <strong>Status:</strong>
                    @php
                    $statusClass = '';
                    $statusLabel = '';
                    switch($stats['trip_details']['trip']->status) {
                    case 'in_progress':
                    $statusClass = 'badge-success';
                    $statusLabel = 'Em andamento';
                    break;
                    case 'finished':
                    $statusClass = 'badge-info';
                    $statusLabel = 'Finalizada';
                    break;
                    case 'cancelled':
                    $statusClass = 'badge-danger';
                    $statusLabel = 'Cancelada';
                    break;
                    default:
                    $statusClass = 'badge-secondary';
                    $statusLabel = 'Agendada';
                    }
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
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

            @if($stats['trip_details']['by_type']->count() > 0)
            <h4 class="mt-4">Alertas por Tipo nesta Viagem</h4>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $totalViagem = $stats['trip_details']['alerts_count'];
                        @endphp

                        @foreach($stats['trip_details']['by_type'] as $item)
                        @php
                        $nomeTipoViagem = '';
                        switch($item->alert_type) {
                        case 'approaching':
                        $nomeTipoViagem = 'Aproximação';
                        break;
                        case 'reached':
                        $nomeTipoViagem = 'Chegada';
                        break;
                        case 'passed':
                        $nomeTipoViagem = 'Passagem';
                        break;
                        case 'end_warning':
                        $nomeTipoViagem = 'Fim da Rota';
                        break;
                        case 'broadcast':
                        $nomeTipoViagem = 'Broadcast';
                        break;
                        default:
                        $nomeTipoViagem = $item->alert_type;
                        }
                        $percentualViagem = $totalViagem > 0 ? ($item->total / $totalViagem) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $nomeTipoViagem }}</td>
                            <td>{{ number_format($item->total) }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {{ $percentualViagem }}%">
                                        {{ number_format($percentualViagem, 1) }}%
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
        @endif
    </div>
</div>
@endsection