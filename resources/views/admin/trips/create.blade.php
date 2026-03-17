@extends('layouts.admin')

@section('content')
    <h2>Criar Trip</h2>

    <form method="POST" action="/admin/trips/store">

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

        <label>Ônibus</label>

        <select name="bus_id" required>
            <option value="">Selecione</option>

            @foreach ($buses as $bus)
                <option value="{{ $bus->id }}">
                    {{ $bus->plate }}
                </option>
            @endforeach

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

        <label>Data</label>
        <input type="date" name="trip_date" required>

        <br><br>

        <label>Horário</label>
        <input type="time" name="start_time" required>

        <br><br>

        <button type="submit">Criar Trip</button>

    </form>
@endsection
