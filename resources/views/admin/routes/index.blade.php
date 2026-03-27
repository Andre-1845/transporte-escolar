@extends('layouts.admin')

@section('title', 'Rotas')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="d-flex-between">
            <h2><i class="fas fa-map"></i> Rotas</h2>

            <a href="{{ route('admin.routes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Rota
            </a>
        </div>
    </div>

    <!-- TABELA -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3>Lista de Rotas</h3>
            </div>

            @if ($routes->count() > 0)

                <div class="table-container mt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($routes as $route)
                                <tr>

                                    <td>#{{ $route->id }}</td>

                                    <td>
                                        <strong>{{ $route->name }}</strong>
                                    </td>

                                    <td>
                                        <span class="badge {{ $route->active ? 'badge-success' : 'badge-danger' }}">
                                            {{ $route->active ? 'Ativa' : 'Inativa' }}
                                        </span>
                                    </td>

                                    <td style="display:flex; gap:6px; flex-wrap:wrap;">

                                        <!-- EDITAR -->
                                        <a href="{{ route('admin.routes.edit', $route->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- MAPA -->
                                        <a href="{{ route('admin.routes.map', $route->id) }}"
                                            class="btn btn-secondary btn-sm">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </a>

                                        <!-- PARADAS -->
                                        <a href="{{ route('admin.routes.stops', $route->id) }}"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-map-pin"></i>
                                        </a>

                                        <!-- EXCLUIR -->
                                        <form method="POST" action="{{ route('admin.routes.destroy', $route->id) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Excluir esta rota?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle"></i>
                    Nenhuma rota cadastrada.

                    <div class="mt-2">
                        <a href="{{ route('admin.routes.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Criar primeira rota
                        </a>
                    </div>
                </div>

            @endif

        </div>
    </div>

@endsection
