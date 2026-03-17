@extends('layouts.admin')

@section('content')
    <h2>Trips</h2>

    <a href="/admin/trips/create">Nova Trip</a>

    <br><br>

    <table border="1" cellpadding="8">

        <thead>
            <tr>
                <th>ID</th>
                <th>Rota</th>
                <th>Motorista</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

            @foreach ($trips as $trip)
                <tr>

                    <td>{{ $trip->id }}</td>

                    <td>
                        {{ $trip->route?->name }}
                    </td>

                    <td>
                        {{ $trip->driver?->name }}
                    </td>

                    <td>
                        {{ $trip->trip_date }}
                    </td>

                    <td>
                        {{ $trip->start_time }}
                    </td>

                    <td>
                        {{ $trip->status }}
                    </td>

                    <td>

                        <a href="/admin/trips/{{ $trip->id }}/edit">
                            Editar
                        </a>

                        @if ($trip->status == 'scheduled')
                            <a href="/admin/trips/{{ $trip->id }}/start">
                                Iniciar
                            </a>
                        @endif

                        @if ($trip->status == 'in_progress')
                            <a href="/admin/trips/{{ $trip->id }}/finish">
                                Finalizar
                            </a>
                        @endif

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>
@endsection
