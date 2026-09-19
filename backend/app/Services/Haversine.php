<?php

namespace App\Services;

use InvalidArgumentException;

class Haversine
{
    /** Great-circle straight-line distance in kilometers; never road distance or travel time. */
    public static function kilometers(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        foreach ([[$fromLatitude, 90], [$toLatitude, 90], [$fromLongitude, 180], [$toLongitude, 180]] as [$value, $limit]) {
            if (! is_finite($value) || abs($value) > $limit) {
                throw new InvalidArgumentException('Coordinates are outside the valid latitude/longitude range.');
            }
        }
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return 6371.0088 * 2 * asin(sqrt(max(0, min(1, $a))));
    }
}
