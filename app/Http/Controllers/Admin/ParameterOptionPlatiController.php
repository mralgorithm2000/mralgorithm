<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DigisellerException;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceOptionMappingResource;
use App\Models\MarketplaceOptionMapping;
use App\Models\Parameter;
use App\Models\ParameterOption;
use App\Services\DigisellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MarketplaceOptionMapping',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'marketplace', type: 'string', example: 'plati'),
        new OA\Property(property: 'parameter_option_id', type: 'integer', example: 1),
        new OA\Property(property: 'marketplace_option_id', type: 'integer', example: 6020801),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class ParameterOptionPlatiController extends Controller
{
    #[OA\Post(
        path: '/api/parameters/{parameter}/options/plati',
        operationId: 'publishParameterOptionsToPlati',
        summary: 'Publish a parameter’s local options to Plati',
        description: 'Requires good_manage_plati_parameters. No request body is required. The endpoint publishes only local options that do not already have a Plati mapping.',
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
                description: 'Options published and mappings created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Parameter options published to Plati successfully.'),
                        new OA\Property(property: 'marketplace_parameter_id', type: 'integer', example: 602080),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/MarketplaceOptionMapping'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_plati_parameters permission'),
            new OA\Response(response: 404, description: 'Parameter not found'),
            new OA\Response(response: 422, description: 'Missing local options or Plati parameter mapping; or Plati validation failed'),
            new OA\Response(response: 502, description: 'Unable to connect to Plati or invalid Plati response'),
        ],
    )]
    public function store(Parameter $parameter, DigisellerService $digiseller): JsonResponse
    {
        Gate::authorize('publishOptionsToPlati', $parameter);

        Log::info('Plati parameter-option publishing started.', [
            'parameter_id' => $parameter->id,
            'user_id' => auth()->id(),
        ]);

        $parameter->load(['options.marketplaceMappings', 'marketplaceMappings']);
        $parameterMapping = $parameter->marketplaceMappings
            ->first(fn ($mapping): bool => strtolower($mapping->marketplace) === 'plati');

        if ($parameterMapping === null) {
            Log::warning('Plati parameter-option publishing stopped: parameter mapping missing.', [
                'parameter_id' => $parameter->id,
                'available_marketplaces' => $parameter->marketplaceMappings->pluck('marketplace')->all(),
            ]);

            return response()->json(['message' => 'The parameter has no Plati parameter mapping.'], 422);
        }

        $options = $parameter->options
            ->filter(fn (ParameterOption $option): bool => ! $option->marketplaceMappings
                ->contains(fn ($mapping): bool => strtolower($mapping->marketplace) === 'plati'))
            ->values();

        if ($parameter->options->isEmpty()) {
            Log::warning('Plati parameter-option publishing stopped: no local options.', [
                'parameter_id' => $parameter->id,
                'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
            ]);

            return response()->json(['message' => 'The parameter has no local options to publish.'], 422);
        }

        if ($options->isEmpty()) {
            Log::info('Plati parameter-option publishing skipped: all options already mapped.', [
                'parameter_id' => $parameter->id,
                'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
                'option_ids' => $parameter->options->pluck('id')->all(),
            ]);

            return response()->json([
                'message' => 'All parameter options are already published to Plati.',
                'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
                'data' => [],
            ]);
        }

        $payloads = $this->variantPayloads($options);

        Log::info('Sending parameter options to Digiseller.', [
            'parameter_id' => $parameter->id,
            'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
            'option_ids' => $options->pluck('id')->all(),
            'variants' => $payloads,
        ]);

        try {
            $variantIds = $digiseller->createProductParameterVariants(
                $parameterMapping->marketplace_parameter_id,
                $payloads,
            );
        } catch (DigisellerException $exception) {
            Log::error('Digiseller rejected parameter options.', [
                'parameter_id' => $parameter->id,
                'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
                'status' => $exception->status,
                'message' => $exception->getMessage(),
                'details' => $exception->details,
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'plati' => $exception->details,
            ], $exception->status);
        }

        $mappings = DB::transaction(function () use ($options, $variantIds): Collection {
            return $options->map(fn (ParameterOption $option, int $index): MarketplaceOptionMapping => $option
                ->marketplaceMappings()
                ->create([
                    'marketplace' => 'plati',
                    'marketplace_option_id' => $variantIds[$index],
                ]));
        });

        Log::info('Plati parameter options published successfully.', [
            'parameter_id' => $parameter->id,
            'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
            'marketplace_option_ids' => $variantIds,
        ]);

        return response()->json([
            'message' => 'Parameter options published to Plati successfully.',
            'marketplace_parameter_id' => $parameterMapping->marketplace_parameter_id,
            'data' => MarketplaceOptionMappingResource::collection($mappings),
        ]);
    }

    /**
     * @param  Collection<int, ParameterOption>  $options
     * @return list<array{name: string, modifier_type: string, rate: float, order: int}>
     */
    private function variantPayloads(Collection $options): array
    {
        return $options->map(fn (ParameterOption $option, int $index): array => [
            'name' => $option->option_name,
            'modifier_type' => match ($option->operator) {
                '-' => 'priceminus',
                '%' => 'percentplus',
                default => 'priceplus',
            },
            'rate' => (float) ($option->additional_price ?? 0),
            'order' => $index + 1,
        ])->all();
    }
}
