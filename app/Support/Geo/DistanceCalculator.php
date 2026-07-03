<?php

namespace App\Support\Geo;

class DistanceCalculator
{
    private const EARTH_RADIUS_KM = 6371;

    public static function haversineKm(float $latFrom, float $lngFrom, float $latTo, float $lngTo): float
    {
        $latFromRad = deg2rad($latFrom);
        $latToRad = deg2rad($latTo);
        $latDelta = deg2rad($latTo - $latFrom);
        $lngDelta = deg2rad($lngTo - $lngFrom);

        $a = sin($latDelta / 2) ** 2
            + cos($latFromRad) * cos($latToRad) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
