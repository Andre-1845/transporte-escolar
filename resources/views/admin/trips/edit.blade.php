<h2>Editar Trip</h2>

<form method="POST" action="/admin/trips/{{ $trip->id }}/update">

    @csrf

    <label>Data</label>
    <input type="date" name="date" value="{{ $trip->date }}">

    <br><br>

    <label>Status</label>

    <select name="status">

        <option value="scheduled">scheduled</option>
        <option value="running">running</option>
        <option value="finished">finished</option>
        <option value="cancelled">cancelled</option>

    </select>

    <br><br>

    <button>Salvar</button>

</form>
