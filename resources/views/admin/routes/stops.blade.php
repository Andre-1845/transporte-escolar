@extends('layouts.admin')

@section('content')
    <h2>Paradas da rota: {{ $route->name }}</h2>

    <h3>Nova parada</h3>

    <form method="POST" action="/admin/routes/{{ $route->id }}/stops">
        @csrf

        <input type="text" name="name" placeholder="Nome da parada" required>

        <input type="number" name="radius_meters" value="200">

        <button type="submit">Adicionar</button>

    </form>

    <hr>

    <table border="1" cellpadding="6">

        <tr>
            <th>Ordem</th>
            <th>Nome</th>
            <th>Raio</th>
            <th>Ações</th>
        </tr>

        @foreach ($stops as $stop)
            <tr>

                <td>{{ $stop->stop_order }}</td>

                <td>

                    <form method="POST" action="/admin/stops/{{ $stop->id }}">
                        @csrf
                        @method('PUT')

                        <input type="text" name="name" value="{{ $stop->name }}">

                </td>

                <td>

                    <input type="number" name="radius_meters" value="{{ $stop->radius_meters }}">

                </td>

                <td>

                    <button type="submit">Salvar</button>

                    </form>

                    <form method="POST" action="/admin/stops/{{ $stop->id }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button>Excluir</button>
                    </form>

                    <form method="POST" action="/admin/stops/{{ $stop->id }}/up" style="display:inline">
                        @csrf
                        <button>↑</button>
                    </form>

                    <form method="POST" action="/admin/stops/{{ $stop->id }}/down" style="display:inline">
                        @csrf
                        <button>↓</button>
                    </form>

                </td>

            </tr>
        @endforeach

    </table>
@endsection
