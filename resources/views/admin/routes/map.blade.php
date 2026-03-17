@extends('layouts.admin')

@section('content')
    <h2>Editor de rota: {{ $route->name }}</h2>

    <div style="margin-bottom:10px">

        <button onclick="setMode('stop')">
            Adicionar parada
        </button>

        <button onclick="setMode('point')">
            Adicionar ponto
        </button>

        <button onclick="generateRoute()">
            Gerar rota automática
        </button>

        <button onclick="saveOrder()">
            Salvar ordem
        </button>

    </div>

    <div id="map" style="height:650px"></div>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}"></script>

    <script>
        let map
        let mode = 'stop'

        let stops = []
        let points = []

        let polyline
        let infoWindow

        function setMode(m) {
            mode = m
        }

        function initMap() {

            map = new google.maps.Map(
                document.getElementById('map'), {
                    zoom: 13,
                    center: {
                        lat: -22.52,
                        lng: -44.10
                    }
                })

            polyline = new google.maps.Polyline({
                map: map,
                strokeColor: "#1976d2",
                strokeWeight: 4
            })

            infoWindow = new google.maps.InfoWindow()

            loadStops()
            loadPoints()

            map.addListener("click", function(e) {

                if (mode === 'stop') {
                    createStop(e.latLng)
                }

                if (mode === 'point') {
                    createPoint(e.latLng)
                }

            })

        }

        function addStopEvents(marker) {

            marker.addListener("click", function() {

                let content = `
<div style="font-family:Arial;font-size:13px;line-height:1.4
">
<b>Parada ${marker.stopOrder}</b><br>
${marker.stopName ?? ''}
</div>
`

                infoWindow.setContent(content)
                infoWindow.open(map, marker)

            })

            marker.addListener("dragend", function(e) {

                updateStop(
                    marker.stopId,
                    e.latLng.lat(),
                    e.latLng.lng()
                )

            })

            marker.addListener("rightclick", function() {

                if (confirm("Excluir parada?")) {

                    deleteStop(marker.stopId)

                    stops = stops.filter(s => s.stopId !== marker.stopId)

                    marker.setMap(null)

                }

            })

        }

        function loadStops() {

            let data = @json($route->stops)

            data.forEach(stop => {

                let marker = new google.maps.Marker({
                    position: {
                        lat: parseFloat(stop.latitude),
                        lng: parseFloat(stop.longitude)
                    },
                    map: map,
                    draggable: true,

                    label: {
                        text: String(stop.stop_order),
                        color: "#ffffff",
                        fontWeight: "bold"
                    },

                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 12,
                        fillColor: "#d32f2f",
                        fillOpacity: 1,
                        strokeColor: "#ffffff",
                        strokeWeight: 2
                    }

                })

                marker.stopId = stop.id
                marker.stopName = stop.name
                marker.stopOrder = stop.stop_order

                addStopEvents(marker)

                stops.push(marker)

            })

        }

        function loadPoints() {

            let data = @json($route->points)

            let path = []

            data.forEach(point => {

                let position = {
                    lat: parseFloat(point.latitude),
                    lng: parseFloat(point.longitude)
                }

                path.push(position)

                let marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    draggable: true,
                    icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                })

                marker.pointId = point.id

                marker.addListener("dragend", function(e) {

                    updatePoint(
                        marker.pointId,
                        e.latLng.lat(),
                        e.latLng.lng()
                    )

                    updatePolyline()

                })

                marker.addListener("rightclick", function() {

                    if (confirm("Excluir ponto?")) {

                        deletePoint(marker.pointId)

                        points = points.filter(p => p.pointId !== marker.pointId)

                        marker.setMap(null)

                        updatePolyline()

                    }

                })

                points.push(marker)

            })

            polyline.setPath(path)

        }

        function createStop(latLng) {

            fetch("/admin/routes/stop", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        route_id: {{ $route->id }},
                        latitude: latLng.lat(),
                        longitude: latLng.lng()
                    })
                })
                .then(r => r.json())
                .then(data => {

                    let stop = data.data

                    let marker = new google.maps.Marker({
                        position: latLng,
                        map: map,
                        draggable: true,

                        label: {
                            text: String(stop.stop_order),
                            color: "#ffffff",
                            fontWeight: "bold"
                        },

                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 12,
                            fillColor: "#d32f2f",
                            fillOpacity: 1,
                            strokeColor: "#ffffff",
                            strokeWeight: 2
                        }

                    })

                    marker.stopId = stop.id
                    marker.stopName = stop.name
                    marker.stopOrder = stop.stop_order

                    addStopEvents(marker)

                    stops.push(marker)

                })

        }

        function createPoint(latLng) {

            fetch("/admin/routes/point", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        route_id: {{ $route->id }},
                        latitude: latLng.lat(),
                        longitude: latLng.lng()
                    })
                })
                .then(r => r.json())
                .then(data => {

                    let marker = new google.maps.Marker({
                        position: latLng,
                        map: map,
                        draggable: true,
                        icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    })

                    marker.pointId = data.data.id

                    points.push(marker)

                    updatePolyline()

                })

        }

        function updatePolyline() {

            let path = []

            points.forEach(p => {
                path.push(p.getPosition())
            })

            polyline.setPath(path)

        }

        function updateStop(id, lat, lng) {

            fetch(`/admin/routes/stop/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            })

        }

        function updatePoint(id, lat, lng) {

            fetch(`/admin/routes/point/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            })

            updatePolyline()

        }

        function deleteStop(id) {

            fetch(`/admin/routes/stop/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })

        }

        function deletePoint(id) {

            fetch(`/admin/routes/point/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })

        }

        function saveOrder() {

            let order = []

            stops.forEach((s, index) => {
                order.push({
                    id: s.stopId,
                    order: index + 1
                })
            })

            fetch("/admin/routes/reorder-stops", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    order: order
                })
            })

        }

        function generateRoute() {

            let stopsData = stops.map(s => ({
                lat: s.getPosition().lat(),
                lng: s.getPosition().lng()
            }))

            if (stopsData.length < 2) {
                alert("Precisa de pelo menos duas paradas")
                return
            }

            let directionsService = new google.maps.DirectionsService()

            let origin = stopsData[0]
            let destination = stopsData[stopsData.length - 1]

            let waypoints = []

            for (let i = 1; i < stopsData.length - 1; i++) {

                waypoints.push({
                    location: stopsData[i],
                    stopover: true
                })

            }

            directionsService.route({

                origin: origin,
                destination: destination,
                waypoints: waypoints,
                travelMode: "DRIVING"

            }, function(result, status) {

                if (status === "OK") {

                    let path = result.routes[0].overview_path

                    polyline.setPath(path)

                    let pointsToSave = []

                    path.forEach((p, index) => {

                        pointsToSave.push({
                            latitude: p.lat(),
                            longitude: p.lng(),
                            order: index
                        })

                    })

                    fetch("/admin/routes/generate-points", {

                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },

                        body: JSON.stringify({
                            route_id: {{ $route->id }},
                            points: pointsToSave
                        })

                    })

                }

            })

        }

        initMap()
    </script>
@endsection
