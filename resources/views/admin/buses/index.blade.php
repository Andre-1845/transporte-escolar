@extends('layouts.admin')

@section('content')
    <h1>Ônibus</h1>

    <a href="{{ route('admin.buses.create') }}">
        Novo ônibus
    </a>

    <table border="1" cellpadding="5">

        <tr>
            <th>Placa</th>
            <th>Modelo</th>
            <th>Capacidade</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>

        @foreach ($buses as $bus)
            <tr>

                <td>{{ $bus->plate }}</td>
                <td>{{ $bus->model }}</td>
                <td>{{ $bus->capacity }}</td>

                <td>
                    @if ($bus->active)
                        Ativo
                    @else
                        Inativo
                    @endif
                </td>

                <td>

                    <a href="{{ route('admin.buses.edit', $bus->id) }}">
                        Editar
                    </a>

                    <form method="POST" action="{{ route('admin.buses.destroy', $bus->id) }}" style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button>Excluir</button>

                    </form>

                </td>

            </tr>
        @endforeach

    </table>
@endsection
