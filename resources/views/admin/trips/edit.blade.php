@extends('layouts.admin')

@section('content')
    <h2>Editar Trip</h2>

    <form method="POST" action="/admin/trips/{{ $trip->id }}/update">

        @csrf

        <label>Rota</label>

        <select name="school_route_id" required>
            <option value="">Selecione</option>

            @foreach ($routes as $route)
                <option value="{{ $route->id }}">
                    {{ $route->name }}
                </option>
            @endforeach

        </select>

        <br><br>

        <label>Data</label>
        <input type="date" name="trip_date" value="{{ $trip->date }}">

        <br><br>

        <label>Horário</label>
        <input type="time" name="start_time" value="{{ $trip->start_time }}" required>
        <br><br>
        <label>Status</label>

        <select name="status">

            <option value="scheduled">scheduled</option>
            <option value="in_progress">in_progress</option>
            <option value="completed">finished</option>
            <option value="cancelled">cancelled</option>

        </select>
        <br><br>
        <label>Motorista</label>

        <select name="driver_id" required>
            <option value="">Selecione</option>

            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}">
                    {{ $driver->name }}
                </option>
            @endforeach

        </select>

        <br><br>

        <label>Ônibus</label>

        <select name="bus_id" required>
            <option value="">Selecione</option>

            @foreach ($buses as $bus)
                <option value="{{ $bus->id }}">
                    {{ $bus->plate }}
                </option>
            @endforeach

        </select>

        <br>
        <hr>

        <button>Salvar</button>

    </form>
@endsection
