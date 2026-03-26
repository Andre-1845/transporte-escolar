@extends('layouts.admin')

@section('title', 'Logs de Alertas')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="info-box">
            <div class="info-box-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="info-box-content">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Logs de Alertas</h2>
                    <div>
                        <a href="{{ route('admin.trip-alerts-logs.stats', request()->query()) }}" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> Estatísticas
                        </a>
                        <a href="{{ route('admin.trip-alerts-logs.export', request()->query()) }}"
                            class="btn btn-success">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="info-box">
            <h3>Filtros</h3>
            <form method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Viagem</label>
                            <select name="trip_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($trips as $trip)
                                <option value="{{ $trip->id }}" {{ request('trip_id') == $trip->id ? 'selected' : '' }}>
                                    #{{ $trip->id }} - {{ $trip->trip_date }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tipo Alerta</label>
                            <select name="alert_type" class="form-control">
                                <option value="">Todos</option>
                                @foreach($alertTypes as $key => $label)
                                <option value="{{ $key }}" {{ request('alert_type') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Data Início</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Data Fim</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Entregue?</label>
                            <select name="delivered" class="form-control">
                                <option value="">Todos</option>
                                <option value="1" {{ request('delivered') == '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ request('delivered') == '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de Logs -->
        <div class="info-box">
            <h3>Registros</h3>

            @if($logs->count() > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Viagem</th>
                            <th>Tipo</th>
                            <th>Stop Point</th>
                            <th>Usuário</th>
                            <th>Distância</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        @php
                        // Definindo variáveis para evitar uso de match() que pode causar problemas
                        $badgeClass = '';
                        $alertLabel = '';

                        switch($log->alert_type) {
                        case 'approaching':
                        $badgeClass = 'badge-warning';
                        $alertLabel = 'Aproximação';
                        break;
                        case 'reached':
                        $badgeClass = 'badge-success';
                        $alertLabel = 'Chegada';
                        break;
                        case 'passed':
                        $badgeClass = 'badge-info';
                        $alertLabel = 'Passagem';
                        break;
                        case 'end_warning':
                        $badgeClass = 'badge-info';
                        $alertLabel = 'Fim Rota';
                        break;
                        case 'broadcast':
                        $badgeClass = 'badge-secondary';
                        $alertLabel = 'Broadcast';
                        break;
                        case 'driver_alert':
                        $badgeClass = 'badge-secondary';
                        $alertLabel = 'Alerta Motorista';
                        break;
                        case 'student_alert':
                        $badgeClass = 'badge-secondary';
                        $alertLabel = 'Alerta Aluno';
                        break;
                        default:
                        $badgeClass = 'badge-secondary';
                        $alertLabel = $log->alert_type;
                        }
                        @endphp
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.trips.edit', $log->trip_id) }}">
                                    #{{ $log->trip_id }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ $alertLabel }}
                                </span>
                            </td>
                            <td>{{ $log->stop ? $log->stop->name : '-' }}</td>
                            <td>{{ $log->user ? $log->user->name : '-' }}</td>
                            <td>
                                @if($log->distance_at_alert)
                                @if($log->distance_at_alert < 1000) {{ round($log->distance_at_alert) }}m @else
                                    {{ number_format($log->distance_at_alert / 1000, 1) }}km @endif @else - @endif </td>
                            <td>
                                @if($log->delivered)
                                <span class="badge badge-success">Entregue</span>
                                @else
                                <span class="badge badge-danger">Falhou</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.trip-alerts-logs.show', $log->id) }}"
                                    class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="pagination">
                {{ $logs->links() }}
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Nenhum registro encontrado.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection