<?php

declare(strict_types=1);

namespace App\Modules\Property\Services;

use App\Modules\Property\Repositories\GeographicLocationRepository;

final class CanonicalGeographyValidator
{
    private const RANKS = ['COUNTRY' => 1, 'GOVERNORATE' => 2, 'CITY' => 3, 'AREA' => 4, 'DISTRICT' => 5];

    public function __construct(private GeographicLocationRepository $locations) {}

    public function accepts(int $id): bool
    {
        $ancestry = $this->locations->loadAncestry($id);
        if ($ancestry['stop_reason'] !== 'root' || $ancestry['locations'] === []) return false;
        $path = array_reverse($ancestry['locations']);
        $previousRank = 0;
        foreach ($path as $index => $location) {
            $rank = self::RANKS[$location['location_type']] ?? 0;
            if ($location['status'] !== 'active' || $rank <= $previousRank) return false;
            if ($index === 0) {
                if ($location['location_type'] !== 'COUNTRY' || $location['parent_id'] !== null) return false;
            } elseif ($location['parent_id'] !== $path[$index - 1]['id']) return false;
            $previousRank = $rank;
        }
        return true;
    }
}
