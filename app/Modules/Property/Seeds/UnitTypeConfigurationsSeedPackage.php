<?php
declare(strict_types=1);

namespace App\Modules\Property\Seeds;

use App\Modules\Property\Services\PropertyCatalogSeedPackage;
use App\Modules\Property\Services\UnitTypeConfigurationService;
use App\Modules\Property\Services\PropertyCatalogService;

final class UnitTypeConfigurationsSeedPackage
{
    public static function make(PropertyCatalogService $catalog, UnitTypeConfigurationService $configurations): PropertyCatalogSeedPackage
    {
        $matrix = self::matrix();
        return new PropertyCatalogSeedPackage('006-unit-type-configurations', 6, json_encode($matrix, JSON_THROW_ON_ERROR), function () use ($catalog, $configurations, $matrix): void {
            $units = array_column($catalog->listUnitTypes(['limit' => 100]), null, 'code');
            $measurements = array_column($catalog->listMeasurementDefinitions(['limit' => 100]), null, 'code');
            $attributes = array_column($catalog->listAttributeDefinitions(['limit' => 100]), null, 'code');
            foreach ($matrix as $unitCode => $rules) {
                if (!isset($units[$unitCode])) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                $unit = $units[$unitCode];
                $existing = $configurations->listConfigurations($unit['id']);
                if ($existing !== []) {
                    if (count($existing) !== 1 || !self::compatible($configurations->aggregate($existing[0]['id']), $rules)) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                    continue;
                }
                $draft = $configurations->createBlankDraft($unit['id'], null, 'SYSTEM_SEED');
                foreach ($rules['measurements'] as $index => [$code, $requirement, $primary]) {
                    if (!isset($measurements[$code])) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                    $configurations->addMeasurementRule($unit['id'], $draft['id'], ['measurement_definition_id' => $measurements[$code]['id'], 'requirement' => $requirement, 'is_primary' => $primary, 'sort_order' => $index + 1]);
                }
                foreach ($rules['attributes'] as $index => [$code, $requirement]) {
                    if (!isset($attributes[$code])) { throw new \RuntimeException('SEED_PACKAGE_COLLISION'); }
                    $configurations->addAttributeRule($unit['id'], $draft['id'], ['attribute_definition_id' => $attributes[$code]['id'], 'requirement' => $requirement, 'sort_order' => $index + 1]);
                }
                $configurations->activate($unit['id'], $draft['id']);
            }
        });
    }

    /** @return array<string, array{measurements:list<array{string,string,bool}>,attributes:list<array{string,string}>}> */
    public static function matrix(): array
    {
        $r = 'REQUIRED'; $o = 'OPTIONAL';
        return [
            'APARTMENT'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOOR',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'DUPLEX'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOOR',$o],['FLOORS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['GARDEN',$o],['MAID_ROOM',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'PENTHOUSE'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOOR',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['MAID_ROOM',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'STUDIO'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['TERRACE_AREA',$o,false]],'attributes'=>[['BATHROOMS',$r],['FLOOR',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'VILLA'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['PLOT_AREA',$o,false],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOORS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['GARDEN',$o],['MAID_ROOM',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'TOWNHOUSE'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['PLOT_AREA',$o,false],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOORS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['GARDEN',$o],['MAID_ROOM',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'TWIN_HOUSE'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['PLOT_AREA',$o,false],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOORS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['GARDEN',$o],['MAID_ROOM',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'CHALET'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['GARDEN_AREA',$o,false],['TERRACE_AREA',$o,false],['ROOF_AREA',$o,false]],'attributes'=>[['BEDROOMS',$r],['BATHROOMS',$r],['FLOOR',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['GARDEN',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'RETAIL_SHOP'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['TERRACE_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['PARKING_SPACES',$o],['FINISHING',$o],['VIEW',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'COMMERCIAL_UNIT'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['PARKING_SPACES',$o],['FINISHING',$o],['VIEW',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'WAREHOUSE'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['PLOT_AREA',$o,false],['NET_AREA',$o,false]],'attributes'=>[['PARKING_SPACES',$o],['FINISHING',$o],['STORAGE',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'OFFICE'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false],['TERRACE_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['BATHROOMS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['VIEW',$o],['BALCONY',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'ADMINISTRATIVE_UNIT'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['BATHROOMS',$o],['PARKING_SPACES',$o],['FINISHING',$o],['VIEW',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'CLINIC'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['NET_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['BATHROOMS',$o],['PARKING_SPACES',$o],['FURNISHING',$o],['FINISHING',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'MEDICAL_CENTER'=>['measurements'=>[['BUILT_UP_AREA',$r,true],['PLOT_AREA',$o,false],['NET_AREA',$o,false]],'attributes'=>[['FLOOR',$o],['FLOORS',$o],['BATHROOMS',$o],['PARKING_SPACES',$o],['FINISHING',$o],['DELIVERY_STATUS',$o],['DELIVERY_DATE',$o],['YEAR_BUILT',$o]]],
            'RESIDENTIAL_LAND'=>['measurements'=>[['PLOT_AREA',$r,true]],'attributes'=>[]], 'COMMERCIAL_LAND'=>['measurements'=>[['PLOT_AREA',$r,true]],'attributes'=>[]], 'AGRICULTURAL_LAND'=>['measurements'=>[['PLOT_AREA',$r,true]],'attributes'=>[]], 'INDUSTRIAL_LAND'=>['measurements'=>[['PLOT_AREA',$r,true]],'attributes'=>[]], 'OTHER'=>['measurements'=>[],'attributes'=>[]],
        ];
    }

    private static function compatible(?array $aggregate, array $expected): bool
    {
        if ($aggregate === null || $aggregate['configuration']['status'] !== 'active' || $aggregate['configuration']['provenance'] !== 'SYSTEM_SEED' || (int) $aggregate['configuration']['version_number'] !== 1) { return false; }
        $measurements = array_map(static fn(array $row): array => [$row['definition']['code'], $row['rule']['requirement'], (bool) $row['rule']['is_primary']], $aggregate['measurement_rules']);
        $attributes = array_map(static fn(array $row): array => [$row['definition']['code'], $row['rule']['requirement']], $aggregate['attribute_rules']);
        return $measurements === $expected['measurements'] && $attributes === $expected['attributes'];
    }
}
