<?php

namespace App\Helpers;

class GeoHelper
{
    public static function distanceMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(
            sqrt(
                pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) *
                    pow(sin($lonDelta / 2), 2)
            )
        );

        return $angle * $earthRadius;
    }
}
