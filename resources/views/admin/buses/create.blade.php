@extends('layouts.admin')

@section('content')
    <h1>Novo Ônibus</h1>

    <form method="POST" action="{{ route('admin.buses.store') }}">

        @csrf

        <label>Placa</label>
        <br>
        <input type="text" name="plate">
        <br><br>

        <label>Modelo</label>
        <br>
        <input type="text" name="model">
        <br><br>

        <label>Capacidade</label>
        <br>
        <input type="number" name="capacity">
        <br><br>

        <label>Ativo</label>
        <input type="checkbox" name="active" value="1" checked>

        <br><br>

        <button type="submit">Salvar</button>

    </form>
@endsection
