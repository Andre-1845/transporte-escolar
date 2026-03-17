@extends('layouts.admin')

@section('content')
    <h1>Editar Usuário</h1>

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">

        @csrf
        @method('PUT')

        <label>Nome</label>
        <br>
        <input type="text" name="name" value="{{ $user->name }}">
        <br><br>

        <label>Email</label>
        <br>
        <input type="email" name="email" value="{{ $user->email }}">
        <br><br>

        <label>Nova senha (opcional)</label>
        <br>
        <input type="password" name="password">
        <br><br>

        <label>Role</label>
        <br>

        <select name="role">

            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @if ($user->hasRole($role->name)) selected @endif>

                    {{ $role->name }}

                </option>
            @endforeach

        </select>

        <br><br>

        <button type="submit">Atualizar</button>

    </form>
@endsection
