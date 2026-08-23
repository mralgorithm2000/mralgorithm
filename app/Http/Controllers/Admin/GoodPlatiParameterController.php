<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DigisellerException;
use App\Http\Controllers\Controller;
use App\Models\Good;
use App\Models\Parameter;
use App\Models\ParameterOption;
use App\Services\DigisellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Log;
use OpenApi\Attributes as OA;

class GoodPlatiParameterController extends Controller
{
    #[OA\Post(
        path: '/api/goods/{good}/parameters/plati', operationId: 'publishGoodParametersToPlati', summary: 'Publish Parameter model records to a Plati product', tags: ['Goods'], security: [['sanctum' => []]],
        description: 'Requires good_manage_plati_parameters. Reads Parameter records and options directly; no request body is required. Parameter titles are sent to Plati for both en-US and ru-RU using the same local title. Existing Plati mappings are reused and missing mappings are saved from IDs returned by Plati.',
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Parameters published successfully', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Good parameters published to Plati successfully.'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'parameter_id', type: 'integer', example: 1),
                            new OA\Property(property: 'marketplace_parameter_id', type: 'integer', example: 602080),
                            new OA\Property(property: 'marketplace_option_ids', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer'), example: ['1' => 6020801]),
                            new OA\Property(property: 'created', type: 'boolean', example: true),
                        ], type: 'object',
                    )),
                ], type: 'object',
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_manage_plati_parameters permission'),
            new OA\Response(response: 404, description: 'Good not found'),
            new OA\Response(response: 422, description: 'Good is unpublished, no parameters exist, or Plati rejected a parameter'),
            new OA\Response(response: 502, description: 'Plati connection or response failure'),
        ],
    )]
    public function store(Good $good, DigisellerService $digiseller): JsonResponse
    {
        Gate::authorize('managePlatiParameters', $good);

        $productId = $good->marketplaceMappings()->where('marketplace', 'plati')->value('marketplace_product_id');

        if ($productId === null) {
            return response()->json(['message' => 'The Good must be published to Plati before its parameters can be published.'], 422);
        }

        $parameters = Parameter::query()
            ->with(['options.marketplaceMappings', 'marketplaceMappings'])
            ->orderBy('id')
            ->get();

        if ($parameters->isEmpty()) {
            return response()->json(['message' => 'There are no parameters to publish.'], 422);
        }

        try {
            $published = $parameters->map(fn (Parameter $parameter): array => $this->publishParameter(
                $digiseller, (int) $productId, $parameter,
            ));

            Log::info('Published parameters to Plati', [
                'good_id' => $good->id,
                'plati_product_id' => $productId,
                'published_parameters' => $published,
            ]);
        } catch (DigisellerException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'plati' => $exception->details,
            ], $exception->status);
        }

        return response()->json([
            'message' => 'Good parameters published to Plati successfully.',
            'data' => $published,
        ]);
    }

    /** @return array<string, mixed> */
    private function publishParameter(DigisellerService $digiseller, int $productId, Parameter $parameter): array
    {
        $mapping = $parameter->marketplaceMappings
            ->first(fn ($mapping): bool => strtolower($mapping->marketplace) === 'plati');

        if ($mapping !== null) {
            $this->publishMissingOptions($digiseller, $parameter, $mapping->marketplace_parameter_id);

            return $this->mappingResponse($parameter, $mapping->marketplace_parameter_id, false);
        }

        $marketplaceParameterId = $digiseller->createProductParameter(
            $productId, $parameter->title, $this->platiType($parameter->type), $parameter->id,
        );
        $parameter->marketplaceMappings()->create([
            'marketplace' => 'plati',
            'marketplace_parameter_id' => $marketplaceParameterId,
        ]);

        $this->publishMissingOptions($digiseller, $parameter, $marketplaceParameterId);

        return $this->mappingResponse($parameter, $marketplaceParameterId, true);
    }

    /** @return array<string, mixed> */
    private function mappingResponse(Parameter $parameter, int $marketplaceParameterId, bool $created): array
    {
        return [
            'parameter_id' => $parameter->id,
            'marketplace_parameter_id' => $marketplaceParameterId,
            'marketplace_option_ids' => (object) $parameter->options->mapWithKeys(function (ParameterOption $option): array {
                $mapping = $option->marketplaceMappings
                    ->first(fn ($mapping): bool => strtolower($mapping->marketplace) === 'plati');

                return $mapping === null ? [] : [(string) $option->id => $mapping->marketplace_option_id];
            })->all(),
            'created' => $created,
        ];
    }

    private function publishMissingOptions(
        DigisellerService $digiseller,
        Parameter $parameter,
        int $marketplaceParameterId,
    ): void {
        $missingOptions = $parameter->options
            ->filter(fn (ParameterOption $option): bool => ! $option->marketplaceMappings
                ->contains(fn ($mapping): bool => strtolower($mapping->marketplace) === 'plati'))
            ->values();

        if ($missingOptions->isEmpty()) {
            return;
        }

        $variantIds = $digiseller->createProductParameterVariants(
            $marketplaceParameterId,
            $missingOptions->map(fn (ParameterOption $option, int $index): array => [
                'name' => $option->option_name,
                'modifier_type' => $this->modifierType($option->operator),
                'rate' => (float) ($option->additional_price ?? 0),
                'order' => $index + 1,
            ])->all(),
        );

        foreach ($missingOptions as $index => $option) {
            $option->marketplaceMappings()->create([
                'marketplace' => 'plati',
                'marketplace_option_id' => $variantIds[$index],
            ]);
        }

        $parameter->load('options.marketplaceMappings');
    }

    private function platiType(string $type): string
    {
        return match ($type) {
            'dropdown' => 'select',
            'radio_button' => 'radio',
            'multiline_textarea' => 'textarea',
            default => $type,
        };
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
