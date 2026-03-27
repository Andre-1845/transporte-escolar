@extends('layouts.admin')

@section('title', 'Detalhes do Alerta #' . $log->id)

@section('content')
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-bell text-primary"></i>
                    <h4 class="mb-0">Detalhes do Alerta #{{ $log->id }}</h4>
                </div>

                <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="row g-3">

            <!-- INFO ALERTA -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Informações do Alerta</strong>
                    </div>

                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <th width="40%">ID</th>
                                    <td>{{ $log->id }}</td>
                                </tr>

                                <tr>
                                    <th>Data/Hora</th>
                                    <td>
                                        {{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>Tipo</th>
                                    <td>
                                        @php
                                            $alertLabels = [
                                                'approaching' => 'Aproximação',
                                                'reached' => 'Chegada',
                                                'passed' => 'Passagem',
                                                'end_warning' => 'Fim da Rota',
                                                'broadcast' => 'Broadcast',
                                                'driver_alert' => 'Motorista',
                                                'student_alert' => 'Aluno',
                                            ];

                                            $badgeClass = match ($log->alert_type) {
                                                'approaching' => 'bg-warning',
                                                'reached' => 'bg-success',
                                                'passed' => 'bg-info',
                                                'end_warning' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };
                                        @endphp

                                        <span class="badge {{ $badgeClass }}">
                                            {{ $alertLabels[$log->alert_type] ?? $log->alert_type }}
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge {{ $log->delivered ? 'bg-success' : 'bg-danger' }}">
                                            {{ $log->delivered ? 'Entregue com sucesso' : 'Falha na entrega' }}
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Distância</th>
                                    <td>
                                        @if ($log->distance_at_alert)
                                            {{ $log->distance_at_alert < 1000
                                                ? round($log->distance_at_alert) . ' m'
                                                : number_format($log->distance_at_alert / 1000, 2) . ' km' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- INFO RELACIONADAS -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Informações Relacionadas</strong>
                    </div>

                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <tbody>

                                <tr>
                                    <th width="40%">Viagem</th>
                                    <td>
                                        <a href="{{ route('admin.trips.edit', $log->trip_id) }}">
                                            #{{ $log->trip_id }}
                                        </a>

                                        @if ($log->trip)
                                            <br>
                                            <small class="text-muted">
                                                {{ $log->trip->trip_date }} - {{ $log->trip->start_time }}
                                            </small>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th>Stop</th>
                                    <td>
                                        @if ($log->stop)
                                            <strong>{{ $log->stop->name }}</strong><br>
                                            <small class="text-muted">
                                                Ordem: {{ $log->stop->stop_order }}
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th>Usuário</th>
                                    <td>
                                        @if ($log->user)
                                            <strong>{{ $log->user->name }}</strong><br>
                                            <small class="text-muted">
                                                {{ $log->user->email }}
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th>Criado em</th>
                                    <td>
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- METADATA -->
        @if ($log->metadata)
            <div class="card mt-3">
                <div class="card-header">
                    <strong>Dados Adicionais (Metadata)</strong>
                </div>

                <div class="card-body">
                    <pre class="bg-light p-3 rounded small">
{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                </pre>
                </div>
            </div>
        @endif

    </div>
@endsection
