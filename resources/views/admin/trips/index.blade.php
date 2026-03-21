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
                <th>Veículo</th>
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
                        {{ $trip->bus?->plate }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($trip->trip_date)->format('d/m/Y') }}
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
                            <form action="{{ url('/admin/trips/' . $trip->id . '/start') }}" method="POST"
                                style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    Iniciar
                                </button>
                            </form>
                        @endif

                        @if ($trip->status == 'in_progress')
                            <form action="{{ url('/admin/trips/' . $trip->id . '/finish') }}" method="POST"
                                style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    Finalizar
                                </button>
                            </form>
                        @endif

                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>
@endsection
