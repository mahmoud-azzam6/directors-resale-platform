<?php
declare(strict_types=1);

namespace App\Modules\Property\Seeds;

use App\Modules\Property\Services\PropertyCatalogSeedPackage;
use App\Modules\Property\Services\PropertyCatalogService;

final class AttributeDefinitionsSeedPackage
{
    public static function make(PropertyCatalogService $catalog): PropertyCatalogSeedPackage
    {
        $rows = [
            ['BEDROOMS', 'INTEGER'], ['BATHROOMS', 'INTEGER'], ['FLOOR', 'INTEGER'], ['FLOORS', 'INTEGER'], ['PARKING_SPACES', 'INTEGER'],
            ['FURNISHING', 'ENUM'], ['FINISHING', 'ENUM'], ['VIEW', 'TEXT'], ['BALCONY', 'BOOLEAN'], ['GARDEN', 'BOOLEAN'], ['MAID_ROOM', 'BOOLEAN'], ['STORAGE', 'BOOLEAN'], ['DELIVERY_STATUS', 'ENUM'], ['DELIVERY_DATE', 'DATE'], ['YEAR_BUILT', 'INTEGER'],
        ];
        return new PropertyCatalogSeedPackage('004-attribute-definitions', 4, json_encode($rows, JSON_THROW_ON_ERROR), function () use ($catalog, $rows): void {
            $existing = [];
            foreach ($catalog->listAttributeDefinitions(['limit' => 100]) as $row) { $existing[$row['code']] = $row; }
            foreach ($rows as $index => [$code, $type]) {
                $sort = $index + 1;
                if (isset($existing[$code])) {
                    $row = $existing[$code];
                    if ($row['provenance'] !== 'SYSTEM_SEED' || $row['status'] !== 'active' || $row['data_type'] !== $type || (int) $row['sort_order'] !== $sort || $row['name_ar'] !== $code || $row['name_en'] !== $code) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                    continue;
                }
                $catalog->createAttributeDefinition(['code' => $code, 'name_ar' => $code, 'name_en' => $code, 'data_type' => $type, 'sort_order' => $sort, 'provenance' => 'SYSTEM_SEED']);
            }
        });
    }
}
