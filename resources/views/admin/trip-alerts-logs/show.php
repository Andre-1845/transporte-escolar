@extends('layouts.admin')

@section('title', 'Detalhes do Alerta #' . $log->id)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="info-box">
            <div class="info-box-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="info-box-content">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Detalhes do Alerta #{{ $log->id }}</h2>
                    <a href="{{ route('admin.trip-alerts-logs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-box">
                    <h3>Informações do Alerta</h3>
                    <table class="table">
                        <tr>
                            <th width="40%">ID do Alerta</th>
                            <td>{{ $log->id }}</td>
                        </tr>
                        <tr>
                            <th>Data/Hora</th>
                            <td>{{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Tipo de Alerta</th>
                            <td>
                                @php
                                $alertLabel = '';
                                $badgeClass = '';
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
                                $alertLabel = 'Fim da Rota';
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
                                <span class="badge {{ $badgeClass }}">
                                    {{ $alertLabel }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status de Entrega</th>
                            <td>
                                @if($log->delivered)
                                <span class="badge badge-success">Entregue com sucesso</span>
                                @else
                                <span class="badge badge-danger">Falha na entrega</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Distância no Alerta</th>
                            <td>
                                @if($log->distance_at_alert)
                                @if($log->distance_at_alert < 1000) {{ round($log->distance_at_alert) }} metros @else
                                    {{ number_format($log->distance_at_alert / 1000, 2) }} km @endif @else - @endif
                                    </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-box">
                    <h3>Informações Relacionadas</h3>
                    <table class="table">
                        <tr>
                            <th width="40%">Viagem</th>
                            <td>
                                <a href="{{ route('admin.trips.edit', $log->trip_id) }}">
                                    #{{ $log->trip_id }}
                                </a>
                                @if($log->trip)
                                <br><small>{{ $log->trip->trip_date }} - {{ $log->trip->start_time }}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Stop Point</th>
                            <td>
                                @if($log->stop)
                                <strong>{{ $log->stop->name }}</strong><br>
                                <small>Ordem: {{ $log->stop->stop_order }}</small>
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Usuário</th>
                            <td>
                                @if($log->user)
                                <strong>{{ $log->user->name }}</strong><br>
                                <small>{{ $log->user->email }}</small>
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Criado em</th>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        @if($log->metadata)
        <div class="info-box">
            <h3>Dados Adicionais (Metadata)</h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                </pre>
        </div>
        @endif
    </div>
</div>
@endsection
