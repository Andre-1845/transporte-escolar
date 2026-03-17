@extends('layouts.admin')

@section('content')
    <h1>Editar rota</h1>

    <form method="POST" action="{{ route('admin.routes.update', $route->id) }}">

        @csrf
        @method('PUT')

        <label>Nome</label>

        <input type="text" name="name" value="{{ $route->name }}">

        <br><br>

        <label>Descrição</label>

        <textarea name="description">
{{ $route->description }}
</textarea>

        <br><br>

        <label>Ativa</label>

        <input type="checkbox" name="active" value="1" @if ($route->active) checked @endif>

        <br><br>

        <button>
            Atualizar
        </button>

    </form>
@endsection
