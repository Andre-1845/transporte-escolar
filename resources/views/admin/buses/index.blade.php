@extends('layouts.admin')

@section('title', 'Gerenciar Ônibus')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <div class="info-box-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="info-box-content">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Gerenciar Ônibus</h2>
                        <a href="{{ route('admin.buses.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Novo Ônibus
                        </a>
                    </div>
                    <p>Gerencie os ônibus cadastrados no sistema</p>
                </div>
            </div>

            <div class="info-box">
                <h3><i class="fas fa-list"></i> Lista de Ônibus</h3>

                @if ($buses->count() > 0)
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Modelo</th>
                                    <th>Capacidade</th>
                                    <th>Status</th>
                                    <th>Ações</th>
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
                                            {{ $bus->capacity }} passageiros
                                        </td>
                                        <td>
                                            @if ($bus->active)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i> Ativo
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times-circle"></i> Inativo
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.buses.edit', $bus->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>

                                            <form method="POST" action="{{ route('admin.buses.destroy', $bus->id) }}"
                                                style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Tem certeza que deseja excluir este ônibus?')">
                                                    <i class="fas fa-trash"></i> Excluir
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if (method_exists($buses, 'links'))
                        <div class="pagination">
                            {{ $buses->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhum ônibus cadastrado.
                        <br><br>
                        <a href="{{ route('admin.buses.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Cadastrar primeiro ônibus
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
