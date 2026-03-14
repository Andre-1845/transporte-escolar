<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'trip_date' => $this->trip_date,
            'start_time' => $this->start_time,

            'status' => $this->status,

            'bus' => $this->whenLoaded('bus', function () {
                return [
                    'id' => $this->bus->id,
                    'plate' => $this->bus->plate,
                    'capacity' => $this->bus->capacity,
                ];
            }),

            'route' => $this->whenLoaded('route', function () {
                return [
                    'id' => $this->route->id,
                    'name' => $this->route->name,
                ];
            }),

            'points' => $this->whenLoaded('route', function () {
                return RoutePointResource::collection($this->route->points);
            }, []),
        ];
    }
}
