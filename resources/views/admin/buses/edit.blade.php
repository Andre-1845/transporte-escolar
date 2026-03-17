@extends('layouts.admin')

@section('content')
    <h1>Editar Ônibus</h1>

    <form method="POST" action="{{ route('admin.buses.update', $bus->id) }}">

        @csrf
        @method('PUT')

        <label>Placa</label>
        <br>
        <input type="text" name="plate" value="{{ $bus->plate }}">
        <br><br>

        <label>Modelo</label>
        <br>
        <input type="text" name="model" value="{{ $bus->model }}">
        <br><br>

        <label>Capacidade</label>
        <br>
        <input type="number" name="capacity" value="{{ $bus->capacity }}">
        <br><br>

        <label>Ativo</label>

        <input type="checkbox" name="active" value="1" @if ($bus->active) checked @endif>

        <br><br>

        <button type="submit">Atualizar</button>

    </form>
@endsection
