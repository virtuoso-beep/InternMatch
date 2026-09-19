<?php

namespace Tests\Unit;

use App\Services\Haversine;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HaversineTest extends TestCase
{
    public function test_known_one_degree_equatorial_distance_is_in_kilometers(): void
    {
        $this->assertEqualsWithDelta(111.19508, Haversine::kilometers(0, 0, 0, 1), 0.00001);
    }

    public function test_identical_poles_antipodes_and_dateline_are_finite(): void
    {
        $this->assertSame(0.0, Haversine::kilometers(7.447, 125.808, 7.447, 125.808));
        $this->assertEqualsWithDelta(0, Haversine::kilometers(90, 0, 90, 180), 0.000001);
        $this->assertEqualsWithDelta(20015.11444, Haversine::kilometers(0, 0, 0, 180), 0.00001);
        $this->assertEqualsWithDelta(222.39016, Haversine::kilometers(0, 179, 0, -179), 0.00001);
    }

    public function test_invalid_coordinate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Haversine::kilometers(91, 0, 0, 0);
    }
}
