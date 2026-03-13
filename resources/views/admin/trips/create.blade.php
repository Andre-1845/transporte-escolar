<h2>Criar Trip</h2>

<form method="POST" action="/admin/trips/store">

    @csrf

    <label>School ID</label>
    <input name="school_id">

    <label>Route ID</label>
    <input name="school_route_id">

    <br><br>

    <label>Bus ID</label>
    <input name="bus_id">

    <br><br>

    <label>Driver ID</label>
    <input name="driver_id">

    <br><br>

    <label>Date</label>
    <input type="date" name="trip_date">

    <br><br>

    <button>Criar</button>

</form>
