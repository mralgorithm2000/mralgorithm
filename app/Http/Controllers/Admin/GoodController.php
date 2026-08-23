<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DigisellerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublishFixedPriceGoodToDigisellerRequest;
use App\Http\Requests\PublishVariablePriceGoodToDigisellerRequest;
use App\Http\Requests\StoreGoodRequest;
use App\Http\Requests\UpdateGoodRequest;
use App\Http\Resources\GoodResource;
use App\Http\Resources\GoodsMarketplaceMappingResource;
use App\Models\Good;
use App\Models\GoodsMarketplaceMapping;
use App\Services\DigisellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GoodDetail',
    required: ['good_key', 'good_name', 'good_value'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'good_key', type: 'string', example: 'region'),
        new OA\Property(property: 'good_name', type: 'string', example: 'Region'),
        new OA\Property(property: 'good_value', type: 'string', example: 'Europe'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'GoodsMarketplaceMapping',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'marketplace', type: 'string', example: 'plati'),
        new OA\Property(property: 'marketplace_product_id', type: 'integer', example: 123456),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Good',
    required: ['name', 'type_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Telegram Stars'),
        new OA\Property(property: 'type_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'type',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'title', type: 'string', example: 'Virtual Number'),
            ],
            type: 'object',
            readOnly: true,
        ),
        new OA\Property(property: 'details', type: 'array', items: new OA\Items(ref: '#/components/schemas/GoodDetail')),
        new OA\Property(
            property: 'marketplace_ids',
            description: 'Marketplace names mapped to their external product IDs.',
            type: 'object',
            readOnly: true,
            example: ['plati' => '6020', 'ggsel' => '6030'],
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
        ),
        new OA\Property(property: 'marketplace_mappings', type: 'array', items: new OA\Items(ref: '#/components/schemas/GoodsMarketplaceMapping'), readOnly: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class GoodController extends Controller
{
    /** @var list<string> */
    private const FIELDS = [
        'name', 'type_id',
    ];

    #[OA\Get(
        path: '/api/goods', operationId: 'listGoods', summary: 'List goods', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'name', description: 'Filter by product name (partial match).', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', description: 'Filter by related type title (partial match).', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated goods list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Good::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        $query = Good::query()->with(['details', 'type', 'marketplaceMappings']);

        if (request()->filled('name')) {
            $query->where('name', 'like', '%'.request()->string('name')->trim().'%');
        }

        if (request()->filled('type')) {
            $type = request('type');
            $query->where('type_id', $type);
        }

        return GoodResource::collection($query->orderByDesc('id')->paginate($perPage)->withQueryString());
    }

    #[OA\Post(
        path: '/api/goods', operationId: 'createGood', summary: 'Create a good', tags: ['Goods'], security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Good')),
        responses: [
            new OA\Response(response: 201, description: 'Good created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreGoodRequest $request): JsonResponse
    {
        Gate::authorize('create', Good::class);
        $validated = $request->validated();

        $good = DB::transaction(function () use ($validated): Good {
            $good = Good::create(Arr::only($validated, self::FIELDS));
            $good->details()->createMany($validated['details'] ?? []);

            return $good;
        });

        return (new GoodResource($good->load(['details', 'type', 'marketplaceMappings'])))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/goods/{good}', operationId: 'showGood', summary: 'Show a good', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Good details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
        ],
    )]
    public function show(Good $good): GoodResource
    {
        Gate::authorize('view', $good);

        return new GoodResource($good->load(['details', 'type', 'marketplaceMappings']));
    }

    #[OA\Put(
        path: '/api/goods/{good}', operationId: 'updateGood', summary: 'Update a good', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Good')),
        responses: [
            new OA\Response(response: 200, description: 'Good updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateGoodRequest $request, Good $good): GoodResource
    {
        Gate::authorize('update', $good);
        $validated = $request->validated();

        DB::transaction(function () use ($good, $validated): void {
            $good->update(Arr::only($validated, self::FIELDS));

            if (array_key_exists('details', $validated)) {
                $good->details()->delete();
                $good->details()->createMany($validated['details']);
            }
        });

        return new GoodResource($good->load(['details', 'type', 'marketplaceMappings']));
    }

    #[OA\Post(
        path: '/api/goods/{good}/digiseller/fixed', operationId: 'publishFixedPriceGoodToDigiseller', summary: 'Publish a fixed-price Good to DigiSeller/Plati', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The server supplies currency USD, content_type text, locale en-US, commission_partner 1, enabled false, cataloguer_category_id 0, and cataloguer_attributes [{"attribute_id": 0, "attribute_value_id": 0}].',
            content: new OA\JsonContent(
                required: ['name', 'description', 'add_info', 'price', 'plati_category_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Telegram Stars'),
                    new OA\Property(property: 'description', type: 'string', example: 'Telegram Stars delivered automatically.'),
                    new OA\Property(property: 'add_info', type: 'string', example: 'Activation instructions and additional product information.'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 12.5),
                    new OA\Property(property: 'plati_category_id', description: 'Plati category sent with owner 1.', type: 'integer', example: 4115),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Product created successfully.'),
                        new OA\Property(property: 'product_id', type: 'integer', example: 123456),
                        new OA\Property(property: 'marketplace_mapping', ref: '#/components/schemas/GoodsMarketplaceMapping'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
            new OA\Response(response: 409, description: 'Good has already been published to DigiSeller'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 502, description: 'DigiSeller request failed'),
        ],
    )]
    public function publishFixedPriceToDigiseller(
        PublishFixedPriceGoodToDigisellerRequest $request,
        Good $good,
        DigisellerService $digiseller,
    ): JsonResponse {
        Gate::authorize('publishFixedPriceToDigiseller', $good);

        return $this->publishDigisellerProduct(
            $good,
            $request->validated(),
            fn (Good $good, array $data): int => $digiseller->createFixedPriceProduct($good, $data),
        );
    }

    #[OA\Post(
        path: '/api/goods/{good}/digiseller/variable', operationId: 'publishVariablePriceGoodToDigiseller', summary: 'Publish a variable-price Good to DigiSeller/Plati', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'The server supplies currency USD, content_type digisellercode, locale en-US, commission_partner 1, enabled false, cataloguer_category_id 0, and cataloguer_attributes [{"attribute_id": 0, "attribute_value_id": 0}].',
            content: new OA\JsonContent(
                required: ['name', 'description', 'add_info', 'price', 'plati_category_id', 'unit_quantity', 'unit_name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Game Currency'),
                    new OA\Property(property: 'description', type: 'string', example: 'Game currency delivered automatically.'),
                    new OA\Property(property: 'add_info', type: 'string', example: 'Activation instructions and additional product information.'),
                    new OA\Property(property: 'price', description: 'Price per configured unit.', type: 'number', format: 'float', example: 12.5),
                    new OA\Property(property: 'unit_quantity', type: 'integer', enum: [1, 10, 100, 1000, 10000, 100000, 1000000, 10000000, 100000000, 1000000000], example: 1000),
                    new OA\Property(property: 'unit_name', type: 'string', example: 'Gold'),
                    new OA\Property(property: 'plati_category_id', description: 'Plati category sent with owner 1.', type: 'integer', example: 4115),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Product created successfully.'),
                        new OA\Property(property: 'product_id', type: 'integer', example: 123456),
                        new OA\Property(property: 'marketplace_mapping', ref: '#/components/schemas/GoodsMarketplaceMapping'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
            new OA\Response(response: 409, description: 'Good has already been published to DigiSeller'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 502, description: 'DigiSeller request failed'),
        ],
    )]
    public function publishVariablePriceToDigiseller(
        PublishVariablePriceGoodToDigisellerRequest $request,
        Good $good,
        DigisellerService $digiseller,
    ): JsonResponse {
        Gate::authorize('update', $good);

        return $this->publishDigisellerProduct(
            $good,
            $request->validated(),
            fn (Good $good, array $data): int => $digiseller->createVariablePriceProduct($good, $data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(Good, array<string, mixed>): int  $createProduct
     */
    private function publishDigisellerProduct(Good $good, array $data, callable $createProduct): JsonResponse
    {
        try {
            $productId = DB::transaction(function () use ($good, $data, $createProduct): ?int {
                $lockedGood = Good::query()
                    ->with(['details', 'type', 'marketplaceMappings'])
                    ->lockForUpdate()
                    ->findOrFail($good->id);

                if ($lockedGood->marketplaceMappings->contains('marketplace', 'plati')) {
                    return null;
                }

                $productId = $createProduct($lockedGood, $data);
                $lockedGood->marketplaceMappings()->create([
                    'marketplace' => 'plati',
                    'marketplace_product_id' => $productId,
                ]);

                return $productId;
            });
        } catch (DigisellerException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'digiseller' => $exception->details,
            ], $exception->status);
        }

        if ($productId === null) {
            return response()->json([
                'message' => 'This Good has already been published to DigiSeller.',
            ], 409);
        }

        return response()->json([
            'message' => 'Product created successfully.',
            'product_id' => $productId,
            'marketplace_mapping' => new GoodsMarketplaceMappingResource(
                GoodsMarketplaceMapping::query()
                    ->where('good_id', $good->id)
                    ->where('marketplace', 'plati')
                    ->firstOrFail(),
            ),
        ]);
    }

    #[OA\Delete(
        path: '/api/goods/{good}', operationId: 'deleteGood', summary: 'Delete a good', tags: ['Goods'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Good deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
        ],
    )]
    public function destroy(Good $good): Response
    {
        Gate::authorize('delete', $good);
        $good->delete();

        return response()->noContent();
    }
}
