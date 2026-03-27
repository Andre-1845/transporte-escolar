@extends('layouts.admin')

@section('title', 'Logs de Alertas')

@section('content')
<div class="admin-container">

    <!-- HEADER -->
    <div class="info-box">
        <div class="info-box-icon">
            <i class="fas fa-bell"></i>
        </div>

        <div class="d-flex-between" style="width: 100%;">
            <h2>Logs de Alertas</h2>

            <div class="d-flex-between">
                <a href="{{ route('admin.trip-alerts-logs.stats', request()->query()) }}" class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> Estatísticas
                </a>

                <a href="{{ route('admin.trip-alerts-logs.export', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-download"></i> Exportar CSV
                </a>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="info-box">
        <h3>Filtros</h3>

        <form method="GET">
            <div class="row">

                <div class="col-md-3">
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

                <div class="col-md-2">
                    <label>Tipo</label>
                    <select name="alert_type" class="form-control">
                        <option value="">Todos</option>
                        @foreach($alertTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('alert_type') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Data Início</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2">
                    <label>Data Fim</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2">
                    <label>Entregue</label>
                    <select name="delivered" class="form-control">
                        <option value="">Todos</option>
                        <option value="1" {{ request('delivered') == '1' ? 'selected' : '' }}>Sim</option>
                        <option value="0" {{ request('delivered') == '0' ? 'selected' : '' }}>Não</option>
                    </select>
                </div>

            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- TABELA -->
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
                        <th>Stop</th>
                        <th>Usuário</th>
                        <th>Distância</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($logs as $log)

                    @php
                    $map = [
                    'approaching' => ['badge-warning','Aproximação'],
                    'reached' => ['badge-success','Chegada'],
                    'passed' => ['badge-info','Passagem'],
                    'end_warning' => ['badge-info','Fim Rota'],
                    'broadcast' => ['badge-secondary','Broadcast'],
                    'driver_alert' => ['badge-secondary','Motorista'],
                    'student_alert' => ['badge-secondary','Aluno'],
                    ];

                    [$badgeClass, $label] = $map[$log->alert_type] ?? ['badge-secondary', $log->alert_type];
                    @endphp

                    <tr>
                        <td>{{ $log->id }}</td>

                        <td>{{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i') }}</td>

                        <td>
                            <a href="{{ route('admin.trips.edit', $log->trip_id) }}">
                                #{{ $log->trip_id }}
                            </a>
                        </td>

                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                        </td>

                        <td>{{ $log->stop->name ?? '-' }}</td>

                        <td>{{ $log->user->name ?? '-' }}</td>

                        <td>
                            @if($log->distance_at_alert)
                            {{ $log->distance_at_alert < 1000
                                            ? round($log->distance_at_alert).'m'
                                            : number_format($log->distance_at_alert/1000,1).'km' }}
                            @else
                            -
                            @endif
                        </td>

                        <td>
                            <span class="badge {{ $log->delivered ? 'badge-success' : 'badge-danger' }}">
                                {{ $log->delivered ? 'Entregue' : 'Falhou' }}
                            </span>
                        </td>

                        <td>
                            <a href="{{ route('admin.trip-alerts-logs.show', $log->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>

                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $logs->links() }}
        </div>

        @else
        <div class="alert alert-info">
            Nenhum registro encontrado.
        </div>
        @endif
    </div>

</div>
@endsection
