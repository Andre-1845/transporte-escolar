@extends('layouts.admin')

@section('content')
    <h1>Novo Usuário</h1>

    <form method="POST" action="{{ route('admin.users.store') }}">

        @csrf

        <label>Nome</label>
        <br>
        <input type="text" name="name">
        <br><br>

        <label>Email</label>
        <br>
        <input type="email" name="email">
        <br><br>

        <label>Senha</label>
        <br>
        <input type="password" name="password">
        <br><br>

        <label>Role</label>
        <br>

        <select name="role">

            @foreach ($roles as $role)
                <option value="{{ $role->name }}">
                    {{ $role->name }}
                </option>
            @endforeach

        </select>

        <br><br>

        <button type="submit">Salvar</button>

    </form>
@endsection
