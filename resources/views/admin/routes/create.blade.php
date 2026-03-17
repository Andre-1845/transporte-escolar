@extends('layouts.admin')

@section('content')
    <h1>Nova rota</h1>

    <form method="POST" action="{{ route('admin.routes.store') }}">

        @csrf

        <label>Nome</label>

        <input type="text" name="name">

        <br><br>

        <label>Descrição</label>

        <textarea name="description"></textarea>

        <br><br>

        <button type="submit">
            Salvar
        </button>

    </form>
@endsection
