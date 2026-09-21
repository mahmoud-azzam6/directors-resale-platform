<?php
declare(strict_types=1);

namespace App\Modules\Property\Seeds;

use App\Modules\Property\Services\PropertyCatalogSeedPackage;
use App\Modules\Property\Services\PropertyCatalogService;

final class MeasurementDefinitionsSeedPackage
{
    public static function make(PropertyCatalogService $catalog): PropertyCatalogSeedPackage
    {
        $rows = [
            ['BUILT_UP_AREA', 'SQM', 1], ['PLOT_AREA', 'SQM', 2], ['NET_AREA', 'SQM', 3],
            ['GARDEN_AREA', 'SQM', 4], ['TERRACE_AREA', 'SQM', 5], ['ROOF_AREA', 'SQM', 6],
        ];
        return new PropertyCatalogSeedPackage('003-measurement-definitions', 3, json_encode($rows, JSON_THROW_ON_ERROR), function () use ($catalog, $rows): void {
            $existing = [];
            foreach ($catalog->listMeasurementDefinitions(['limit' => 100]) as $row) { $existing[$row['code']] = $row; }
            foreach ($rows as [$code, $unit, $sort]) {
                if (isset($existing[$code])) {
                    $row = $existing[$code];
                    if ($row['provenance'] !== 'SYSTEM_SEED' || $row['status'] !== 'active' || $row['default_unit_code'] !== $unit || (int) $row['sort_order'] !== $sort || $row['name_ar'] !== $code || $row['name_en'] !== $code) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                    continue;
                }
                $catalog->createMeasurementDefinition(['code' => $code, 'name_ar' => $code, 'name_en' => $code, 'default_unit_code' => $unit, 'sort_order' => $sort, 'provenance' => 'SYSTEM_SEED']);
            }
        });
    }
}
