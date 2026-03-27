@extends('layouts.admin')

@section('title', 'Usuários')

@section('content')

    <!-- HEADER -->
    <div class="section">
        <div class="d-flex-between">
            <h2><i class="fas fa-users"></i> Usuários</h2>

            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Usuário
            </a>
        </div>
    </div>

    <!-- TABELA -->
    <div class="section">
        <div class="info-box" style="flex-direction: column; align-items: stretch;">

            <div class="section-header">
                <h3>Lista de Usuários</h3>
            </div>

            @if ($users->count() > 0)

                <div class="table-container mt-2">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Perfis</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($users as $user)
                                <tr>

                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                    </td>

                                    <td>{{ $user->email }}</td>

                                    <td>
                                        @forelse ($user->roles as $role)
                                            @php
                                                $roleName = strtolower($role->name);

                                                $class = match ($roleName) {
                                                    'driver' => 'badge-success',
                                                    'admin' => 'badge-primary',
                                                    default => 'badge-warning',
                                                };
                                            @endphp

                                            <span class="badge {{ $class }}">
                                                {{ ucfirst($role->name) }}
                                            </span>

                                        @empty
                                            <span class="badge badge-secondary">
                                                Sem perfil
                                            </span>
                                        @endforelse
                                    </td>

                                    <td style="display:flex; gap:6px;">

                                        <!-- EDITAR -->
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- EXCLUIR -->
                                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Excluir este usuário?')">
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
                    Nenhum usuário cadastrado.

                    <div class="mt-2">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Criar primeiro usuário
                        </a>
                    </div>
                </div>

            @endif

        </div>
    </div>

@endsection
