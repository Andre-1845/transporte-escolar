@extends('layouts.admin')

@section('content')
    <h1>Rotas</h1>

    <a href="{{ route('admin.routes.create') }}">
        Nova rota
    </a>

    <table border="1" cellpadding="6">

        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Ativa</th>
            <th>Ações</th>
        </tr>

        @foreach ($routes as $route)
            <tr>

                <td>{{ $route->id }}</td>

                <td>{{ $route->name }}</td>

                <td>
                    @if ($route->active)
                        Ativa
                    @else
                        Inativa
                    @endif
                </td>

                <td>

                    <a href="{{ route('admin.routes.edit', $route->id) }}">
                        Editar
                    </a>

                    |

                    <a href="{{ route('admin.routes.map', $route->id) }}">
                        🗺 Mapa
                    </a>

                    |<a href="{{ route('admin.routes.stops', $route->id) }}">
                        Paradas
                    </a>

                    <form method="POST" action="{{ route('admin.routes.destroy', $route->id) }}" style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button>Excluir</button>

                    </form>

                </td>

            </tr>
        @endforeach

    </table>
@endsection
