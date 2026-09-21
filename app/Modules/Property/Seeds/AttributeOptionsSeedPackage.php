<?php
declare(strict_types=1);

namespace App\Modules\Property\Seeds;

use App\Modules\Property\Services\PropertyCatalogSeedPackage;
use App\Modules\Property\Services\PropertyCatalogService;

final class AttributeOptionsSeedPackage
{
    public static function make(PropertyCatalogService $catalog): PropertyCatalogSeedPackage
    {
        $rows = ['FURNISHING' => ['UNFURNISHED', 'SEMI_FURNISHED', 'FURNISHED'], 'FINISHING' => ['UNFINISHED', 'SEMI_FINISHED', 'FINISHED', 'LUXURY_FINISHED'], 'DELIVERY_STATUS' => ['READY', 'UNDER_CONSTRUCTION']];
        return new PropertyCatalogSeedPackage('005-attribute-options', 5, json_encode($rows, JSON_THROW_ON_ERROR), function () use ($catalog, $rows): void {
            $definitions = [];
            foreach ($catalog->listAttributeDefinitions(['limit' => 100]) as $row) { $definitions[$row['code']] = $row; }
            foreach ($rows as $definitionCode => $codes) {
                if (!isset($definitions[$definitionCode]) || $definitions[$definitionCode]['data_type'] !== 'ENUM') { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                $existing = [];
                foreach ($catalog->listAttributeOptions($definitions[$definitionCode]['id'], ['limit' => 100]) as $row) { $existing[$row['code']] = $row; }
                foreach ($codes as $index => $code) {
                    $sort = $index + 1;
                    if (isset($existing[$code])) {
                        $row = $existing[$code];
                        if ($row['provenance'] !== 'SYSTEM_SEED' || $row['status'] !== 'active' || (int) $row['sort_order'] !== $sort || $row['name_ar'] !== $code || $row['name_en'] !== $code) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                        continue;
                    }
                    $catalog->createAttributeOption($definitions[$definitionCode]['id'], ['code' => $code, 'name_ar' => $code, 'name_en' => $code, 'sort_order' => $sort, 'provenance' => 'SYSTEM_SEED']);
                }
            }
        });
    }
}
