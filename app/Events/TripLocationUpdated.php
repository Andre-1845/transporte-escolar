<?php
// app/Events/TripLocationUpdated.php

namespace App\Events;

use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    public $location;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(Trip $trip, TripLocation $location)
    {
        $this->trip = [
            'id' => $trip->id,
            'status' => $trip->status,
        ];

        $this->location = [
            'lat' => (float) $location->latitude,
            'lng' => (float) $location->longitude,
            'recorded_at' => $location->recorded_at->toIso8601String(),
        ];

        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Canal privado para a trip específica
            new Channel('trip.' . $this->trip['id']),

            // Canal para a escola (útil para central de monitoramento)
            new Channel('school.' . request()->user()->school_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->trip['id'],
            'location' => $this->location,
            'timestamp' => $this->timestamp,
        ];
    }
}
