@extends('layouts.admin')

@section('content')
    <h1>Usuários</h1>

    <a href="{{ route('admin.users.create') }}">Novo usuário</a>

    <table border="1" cellpadding="5">

        <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Ações</th>
        </tr>

        @foreach ($users as $user)
            <tr>

                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>

                <td>
                    @foreach ($user->roles as $role)
                        {{ $role->name }}
                    @endforeach
                </td>

                <td>

                    <a href="{{ route('admin.users.edit', $user->id) }}">
                        Editar
                    </a>

                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button type="submit">Excluir</button>

                    </form>

                </td>

            </tr>
        @endforeach

    </table>
@endsection
