<?php

declare(strict_types=1);

namespace App\Modules\Property\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Modules\Property\Services\PropertyCatalogService;
use App\Responses\Response;

final class PropertyCatalogReadController
{
    public function __construct(private PropertyCatalogService $service) {}

    public function categories(Request $request): Response { return $this->listing($request, fn (array $options): array => $this->service->listCategories($options), 'Property Categories'); }
    public function category(Request $request, int|string $id): Response { return $this->item(fn (): ?array => $this->service->findCategory($id), 'Property Category'); }
    public function unitTypes(Request $request): Response { return $this->listing($request, fn (array $options): array => $this->service->listUnitTypes($options), 'Unit Types', ['property_category_id']); }
    public function unitType(Request $request, int|string $id): Response { return $this->item(fn (): ?array => $this->service->findUnitType($id), 'Unit Type'); }
    public function measurementDefinitions(Request $request): Response { return $this->listing($request, fn (array $options): array => $this->service->listMeasurementDefinitions($options), 'Measurement Definitions'); }
    public function measurementDefinition(Request $request, int|string $id): Response { return $this->item(fn (): ?array => $this->service->findMeasurementDefinition($id), 'Measurement Definition'); }
    public function attributeDefinitions(Request $request): Response { return $this->listing($request, fn (array $options): array => $this->service->listAttributeDefinitions($options), 'Attribute Definitions', ['data_type']); }
    public function attributeDefinition(Request $request, int|string $id): Response { return $this->item(fn (): ?array => $this->service->findAttributeDefinition($id), 'Attribute Definition'); }

    public function attributeOptions(Request $request, int|string $definitionId): Response
    {
        return $this->item(function () use ($request, $definitionId): ?array {
            $definition = $this->service->findAttributeDefinition($definitionId);
            if ($definition === null) { return null; }
            return ['definition' => $definition, 'options' => $this->service->listAttributeOptions($definitionId, $this->options($request))];
        }, 'Attribute Definition');
    }

    private function listing(Request $request, callable $operation, string $name, array $extra = []): Response
    {
        try { return Response::success("{$name} retrieved.", $operation($this->options($request, $extra))); }
        catch (ValidationException $exception) { return $this->validation($exception); }
    }

    private function item(callable $operation, string $name): Response
    {
        try { $record = $operation(); return $record === null ? Response::error('not_found', "{$name} not found.", 404) : Response::success("{$name} retrieved.", $record); }
        catch (ValidationException $exception) { return $this->validation($exception); }
    }

    private function options(Request $request, array $extra = []): array
    {
        $options = [];
        foreach (array_merge(['status', 'search', 'limit', 'offset'], $extra) as $key) {
            $value = $request->query($key);
            if ($value === null) { continue; }
            if (in_array($key, ['limit', 'offset', 'property_category_id'], true)) {
                if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value))) || (int) $value < 0 || ($key === 'limit' && (int) $value === 0)) { throw new ValidationException([$key => 'Must be a valid positive integer.']); }
                $options[$key] = (int) $value;
                continue;
            }
            if (! is_string($value)) { throw new ValidationException([$key => 'Must be a string.']); }
            $options[$key] = $value;
        }
        return $options;
    }

    private function validation(ValidationException $exception): Response
    {
        return Response::json(['success' => false, 'error' => ['code' => 'validation_error', 'message' => 'The Property Catalog query is invalid.', 'fields' => $exception->errors()]], 422);
    }
}
