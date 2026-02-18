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
            'status' => $this->status,

            'bus' => [
                'id' => $this->bus?->id,
                'plate' => $this->bus?->plate,
                'capacity' => $this->bus?->capacity,
            ],

            'route' => [
                'id' => $this->route?->id,
                'name' => $this->route?->name,
            ],

            'points' => $this->route
                ? RoutePointResource::collection($this->route->points)
                : [],
        ];
    }
}
