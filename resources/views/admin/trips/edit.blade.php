@extends('layouts.admin')

@section('content')
    <h2>Editar Trip</h2>

    <form method="POST" action="/admin/trips/{{ $trip->id }}/update">

        @csrf

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
            <option value="completed">completed</option>
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

        <button>Salvar</button>

    </form>
@endsection
