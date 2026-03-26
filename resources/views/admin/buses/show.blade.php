@extends('layouts.admin')

@section('title', 'Detalhes do Ônibus')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <div class="info-box-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="info-box-content">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Detalhes do Ônibus</h2>
                        <div>
                            <a href="{{ route('admin.buses.edit', $bus->id) }}" class="btn btn-info">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="{{ route('admin.buses.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                    <p>Visualização detalhada do ônibus: <strong>{{ $bus->plate }}</strong></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <h3><i class="fas fa-info-circle"></i> Informações Principais</h3>
                        <table class="table">
                            <tr>
                                <th width="40%">Placa</th>
                                <td><strong>{{ $bus->plate }}</strong></td>
                            </tr>
                            <tr>
                                <th>Modelo</th>
                                <td>{{ $bus->model ?: 'Não informado' }}</td>
                            </tr>
                            <tr>
                                <th>Capacidade</th>
                                <td>{{ $bus->capacity }} passageiros</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($bus->active)
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-danger">Inativo</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <h3><i class="fas fa-clock"></i> Informações do Sistema</h3>
                        <table class="table">
                            <tr>
                                <th width="40%">ID do Ônibus</th>
                                <td>#{{ $bus->id }}</td>
                            </tr>
                            <tr>
                                <th>Criado em</th>
                                <td>{{ \Carbon\Carbon::parse($bus->created_at)->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Última atualização</th>
                                <td>{{ \Carbon\Carbon::parse($bus->updated_at)->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Trips associadas a este ônibus -->
            @if ($bus->trips && $bus->trips->count() > 0)
                <div class="info-box">
                    <h3><i class="fas fa-bus"></i> Viagens realizadas</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Rota</th>
                                    <th>Motorista</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                            </thead>
                            <tbody>
                                @foreach ($bus->trips as $trip)
                                    <tr>
                                        <td>#{{ $trip->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($trip->trip_date)->format('d/m/Y') }}</td>
                                        <td>{{ $trip->route->name ?? 'N/A' }}</td>
                                        <td>{{ $trip->driver->name ?? 'N/A' }}</td>
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
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
