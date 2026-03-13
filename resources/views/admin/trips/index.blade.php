<h2>Trips</h2>

<a href="/admin/trips/create">Nova Trip</a>

<table border="1" cellpadding="6">

    <tr>
        <th>ID</th>
        <th>Data</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    @foreach ($trips as $trip)
        <tr>

            <td>{{ $trip->id }}</td>
            <td>{{ $trip->trip_date }}</td>
            <td>{{ $trip->status }}</td>

            <td>

                <a href="/admin/trips/{{ $trip->id }}/edit">Editar</a>

                <form action="/admin/trips/{{ $trip->id }}/start" method="POST" style="display:inline">
                    @csrf
                    <button>Start</button>
                </form>

                <form action="/admin/trips/{{ $trip->id }}/finish" method="POST" style="display:inline">
                    @csrf
                    <button>Finish</button>
                </form>

            </td>

        </tr>
    @endforeach

</table>
