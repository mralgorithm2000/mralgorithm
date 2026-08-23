<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MarketplaceApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteParameterOptionRequest;
use App\Http\Requests\StoreParameterOptionRequest;
use App\Http\Requests\UpdateParameterOptionRequest;
use App\Http\Resources\MarketplaceOptionMappingResource;
use App\Http\Resources\ParameterOptionResource;
use App\Models\Parameter;
use App\Models\ParameterOption;
use App\Services\MarketplaceOptionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class ParameterOptionController extends Controller
{
    #[OA\Get(
        path: '/api/parameters/{parameter}/options',
        operationId: 'listParameterOptions',
        summary: 'List the local options for a parameter',
        description: 'Returns all options belonging to the route-bound parameter. Requires good_manage_parameters.',
        tags: ['Parameters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'parameter',
                description: 'Parameter ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Parameter options',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ParameterOption'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_parameters permission'),
            new OA\Response(response: 404, description: 'Parameter not found'),
        ],
    )]
    public function index(Parameter $parameter): AnonymousResourceCollection
    {
        Gate::authorize('viewOptions', $parameter);

        return ParameterOptionResource::collection(
            $parameter->options()->with('supplier')->orderBy('id')->get(),
        );
    }

    #[OA\Post(
        path: '/api/parameters/{parameter}/options',
        operationId: 'createParameterOption',
        summary: 'Create a local option for a parameter',
        description: 'Stores the option locally using the route parameter as parameter_id. It does not publish or synchronize the option with a marketplace. Requires good_manage_parameters.',
        tags: ['Parameters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'parameter',
                description: 'Parameter ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['option_name', 'option_value'],
                properties: [
                    new OA\Property(property: 'option_name', type: 'string', maxLength: 255, example: 'Europe'),
                    new OA\Property(property: 'option_value', type: 'string', maxLength: 255, example: 'EU'),
                    new OA\Property(property: 'operator', type: 'string', enum: ['+', '-', '%'], nullable: true, example: '+'),
                    new OA\Property(property: 'additional_price', type: 'number', format: 'float', minimum: 0, nullable: true, example: 2.5),
                    new OA\Property(property: 'original_price', type: 'number', format: 'double', minimum: 0, maximum: 999999999999.999999, nullable: true, example: 10.5),
                    new OA\Property(property: 'selling_price', type: 'number', format: 'double', minimum: 0, maximum: 999999999999.999999, nullable: true, example: 12.5),
                    new OA\Property(property: 'supplier_id', description: 'Must reference an existing supplier.', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'supplier_product_id', type: 'string', maxLength: 255, nullable: true, example: 'SUP-EU-001'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Parameter option created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ParameterOption'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_parameters permission'),
            new OA\Response(response: 404, description: 'Parameter not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreParameterOptionRequest $request, Parameter $parameter): JsonResponse
    {
        Gate::authorize('createOption', $parameter);

        $option = $parameter->options()->create($request->validated());

        return (new ParameterOptionResource($option->load('supplier')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/parameters/{parameter}/options/{option}',
        operationId: 'updateParameterOption',
        summary: 'Update a parameter option locally and in selected marketplaces',
        description: 'Requires good_manage_plati_parameters. Only marketplaces listed in the request are synchronized. Marketplace updates run before the local update; local fields remain unchanged if synchronization fails. An empty marketplaces array performs a local-only update. Currently supported: plati.',
        tags: ['Parameters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'parameter', description: 'Parameter ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'option', description: 'Local ParameterOption ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['option_name', 'option_value', 'marketplaces'],
                properties: [
                    new OA\Property(property: 'option_name', type: 'string', maxLength: 255, example: 'Europe'),
                    new OA\Property(property: 'option_value', type: 'string', maxLength: 255, example: 'EU'),
                    new OA\Property(property: 'operator', type: 'string', enum: ['+', '-', '%'], nullable: true, example: '+'),
                    new OA\Property(property: 'additional_price', type: 'number', format: 'float', minimum: 0, nullable: true, example: 2.5),
                    new OA\Property(
                        property: 'marketplaces',
                        description: 'Marketplaces to synchronize. An empty array updates only the local option. Currently supported: plati.',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['plati']),
                        example: ['plati'],
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Parameter option updated locally and in all selected marketplaces',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ParameterOption'),
                        new OA\Property(
                            property: 'marketplace_mappings',
                            description: 'Mappings for the marketplaces selected for synchronization.',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/MarketplaceOptionMapping'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_plati_parameters permission'),
            new OA\Response(response: 404, description: 'Parameter or option not found, or option does not belong to parameter'),
            new OA\Response(response: 422, description: 'Validation failed, a marketplace is unsupported, or a required marketplace mapping is missing'),
            new OA\Response(response: 502, description: 'Marketplace connection or response failure'),
        ],
    )]
    public function update(
        UpdateParameterOptionRequest $request,
        Parameter $parameter,
        ParameterOption $option,
        MarketplaceOptionSyncService $marketplaces,
    ): JsonResponse {
        Gate::authorize('updatePlatiOption', $parameter);

        abort_unless($option->parameter_id === $parameter->id, 404);

        $validated = $request->validated();
        $selectedMarketplaces = collect($validated['marketplaces'])
            ->map(fn (string $marketplace): string => strtolower($marketplace))
            ->unique()
            ->values();

        $unsupported = $selectedMarketplaces->diff($marketplaces->supportedMarketplaces())->values();
        if ($unsupported->isNotEmpty()) {
            return response()->json([
                'message' => 'One or more selected marketplaces are not supported.',
                'unsupported_marketplaces' => $unsupported,
                'supported_marketplaces' => $marketplaces->supportedMarketplaces(),
            ], 422);
        }

        $optionMappings = $this->mappingsByMarketplace($option->marketplaceMappings, $selectedMarketplaces);
        $parameterMappings = $this->mappingsByMarketplace($parameter->marketplaceMappings, $selectedMarketplaces);

        foreach ($selectedMarketplaces as $marketplace) {
            if (! $optionMappings->has($marketplace)) {
                return response()->json([
                    'message' => "The option has no {$marketplace} marketplace mapping.",
                ], 422);
            }

            if (! $parameterMappings->has($marketplace)) {
                return response()->json([
                    'message' => "The parameter has no {$marketplace} marketplace mapping.",
                ], 422);
            }
        }

        try {
            foreach ($selectedMarketplaces as $marketplace) {
                $marketplaces->update(
                    $marketplace,
                    $parameterMappings->get($marketplace)->marketplace_parameter_id,
                    $optionMappings->get($marketplace)->marketplace_option_id,
                    [
                        'name' => $validated['option_name'],
                        'modifier_type' => $this->modifierType($validated['operator'] ?? null),
                        'rate' => (float) ($validated['additional_price'] ?? 0),
                    ],
                );
            }
        } catch (MarketplaceApiException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'marketplace' => $exception->marketplace,
                'details' => $exception->details,
            ], $exception->status);
        }

        $option->update(Arr::except($validated, 'marketplaces'));

        return response()->json([
            'data' => new ParameterOptionResource($option->refresh()),
            'marketplace_mappings' => MarketplaceOptionMappingResource::collection($optionMappings->values()),
        ]);
    }

    #[OA\Delete(
        path: '/api/parameters/{parameter}/options/{option}',
        operationId: 'deleteParameterOption',
        summary: 'Soft-delete a parameter option locally and remove it from selected marketplaces',
        description: 'Requires good_manage_plati_parameters. Only marketplaces listed in the request are affected. Marketplace deletions run before the local soft delete; the local option remains active if a marketplace call fails. An empty marketplaces array performs a local-only soft delete. Currently supported: plati.',
        tags: ['Parameters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'parameter', description: 'Parameter ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'option', description: 'Local ParameterOption ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['marketplaces'],
                properties: [
                    new OA\Property(
                        property: 'marketplaces',
                        description: 'Marketplaces from which the option should be removed. An empty array performs only the local soft delete.',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['plati']),
                        example: ['plati'],
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Option removed from selected marketplaces and soft-deleted locally',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Parameter option deleted successfully.'),
                        new OA\Property(property: 'option_id', type: 'integer', example: 1),
                        new OA\Property(property: 'deleted_from_marketplaces', type: 'array', items: new OA\Items(type: 'string'), example: ['plati']),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_plati_parameters permission'),
            new OA\Response(response: 404, description: 'Parameter or option not found, or option does not belong to parameter'),
            new OA\Response(response: 422, description: 'Validation failed, a marketplace is unsupported, or a required marketplace mapping is missing'),
            new OA\Response(response: 502, description: 'Marketplace connection or response failure'),
        ],
    )]
    public function destroy(
        DeleteParameterOptionRequest $request,
        Parameter $parameter,
        ParameterOption $option,
        MarketplaceOptionSyncService $marketplaces,
    ): JsonResponse {
        Gate::authorize('deleteMarketplaceOption', $parameter);

        abort_unless($option->parameter_id === $parameter->id, 404);

        $selectedMarketplaces = collect($request->validated('marketplaces'))
            ->map(fn (string $marketplace): string => strtolower($marketplace))
            ->unique()
            ->values();

        $unsupported = $selectedMarketplaces->diff($marketplaces->supportedMarketplaces())->values();
        if ($unsupported->isNotEmpty()) {
            return response()->json([
                'message' => 'One or more selected marketplaces are not supported.',
                'unsupported_marketplaces' => $unsupported,
                'supported_marketplaces' => $marketplaces->supportedMarketplaces(),
            ], 422);
        }

        $optionMappings = $this->mappingsByMarketplace($option->marketplaceMappings, $selectedMarketplaces);
        $parameterMappings = $this->mappingsByMarketplace($parameter->marketplaceMappings, $selectedMarketplaces);

        foreach ($selectedMarketplaces as $marketplace) {
            if (! $optionMappings->has($marketplace)) {
                return response()->json([
                    'message' => "The option has no {$marketplace} marketplace mapping.",
                ], 422);
            }

            if (! $parameterMappings->has($marketplace)) {
                return response()->json([
                    'message' => "The parameter has no {$marketplace} marketplace mapping.",
                ], 422);
            }
        }

        try {
            foreach ($selectedMarketplaces as $marketplace) {
                $marketplaces->delete(
                    $marketplace,
                    $parameterMappings->get($marketplace)->marketplace_parameter_id,
                    $optionMappings->get($marketplace)->marketplace_option_id,
                );
            }
        } catch (MarketplaceApiException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'marketplace' => $exception->marketplace,
                'details' => $exception->details,
            ], $exception->status);
        }

        $option->delete();

        return response()->json([
            'message' => 'Parameter option deleted successfully.',
            'option_id' => $option->id,
            'deleted_from_marketplaces' => $selectedMarketplaces,
        ]);
    }

    /**
     * @param  Collection<int, mixed>  $mappings
     * @param  Collection<int, string>  $marketplaces
     * @return Collection<string, mixed>
     */
    private function mappingsByMarketplace(Collection $mappings, Collection $marketplaces): Collection
    {
        return $mappings
            ->filter(fn ($mapping): bool => $marketplaces->contains(strtolower($mapping->marketplace)))
            ->keyBy(fn ($mapping): string => strtolower($mapping->marketplace));
    }

    private function modifierType(?string $operator): string
    {
        return match ($operator) {
            '-' => 'priceminus',
            '%' => 'percentplus',
            default => 'priceplus',
        };
    }
}
