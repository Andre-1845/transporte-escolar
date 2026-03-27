@extends('layouts.admin')

@section('title', 'Ônibus')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="info-box">

            <div class="info-box-icon">
                <i class="fas fa-truck"></i>
            </div>

            <div class="d-flex-between" style="width:100%;">
                <div>
                    <h2>Gerenciar Ônibus</h2>
                    <p>Controle dos veículos cadastrados no sistema</p>
                </div>

                <a href="{{ route('admin.buses.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Novo Ônibus
                </a>
            </div>

        </div>
    </div>

    <!-- LISTA -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3><i class="fas fa-list"></i> Lista de Ônibus</h3>
            </div>

            @if ($buses->count() > 0)

                <div class="table-container mt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Capacidade</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($buses as $bus)
                                <tr>

                                    <td>
                                        <strong>{{ $bus->plate }}</strong>
                                    </td>

                                    <td>{{ $bus->model }}</td>

                                    <td>
                                        <i class="fas fa-users"></i>
                                        {{ $bus->capacity }}
                                    </td>

                                    <td>
                                        <span class="badge {{ $bus->active ? 'badge-success' : 'badge-danger' }}">
                                            <i class="fas {{ $bus->active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                            {{ $bus->active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>

                                    <td style="display:flex; gap:6px;">

                                        <!-- EDITAR -->
                                        <a href="{{ route('admin.buses.edit', $bus->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- EXCLUIR -->
                                        <form method="POST" action="{{ route('admin.buses.destroy', $bus->id) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Excluir este ônibus?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- PAGINAÇÃO -->
                @if (method_exists($buses, 'links'))
                    <div class="pagination mt-2">
                        {{ $buses->links() }}
                    </div>
                @endif
            @else
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle"></i>
                    Nenhum ônibus cadastrado.

                    <div class="mt-2">
                        <a href="{{ route('admin.buses.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Cadastrar primeiro ônibus
                        </a>
                    </div>
                </div>

            @endif

        </div>
    </div>

@endsection
