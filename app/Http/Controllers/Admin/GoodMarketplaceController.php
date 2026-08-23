<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Good;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class GoodMarketplaceController extends Controller
{
    #[OA\Get(
        path: '/api/goods/{good}/marketplaces',
        operationId: 'listGoodMarketplaceProductIds',
        summary: 'Get marketplace product IDs connected to a good',
        description: 'Requires the good_view permission. Returns an empty object when the good has no marketplace mappings.',
        tags: ['Goods'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'good',
                description: 'Good ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Marketplace names mapped to their product IDs',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            example: ['plati' => '6020', 'ggsel' => '6030'],
                            additionalProperties: new OA\AdditionalProperties(type: 'string'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing good_view permission'),
            new OA\Response(response: 404, description: 'Good not found'),
        ],
    )]
    public function index(Good $good): JsonResponse
    {
        Gate::authorize('viewMarketplaces', $good);

        $marketplaces = $good->marketplaceMappings()
            ->orderBy('marketplace')
            ->pluck('marketplace_product_id', 'marketplace')
            ->map(fn (int|string $productId): string => (string) $productId);

        return response()->json([
            'data' => (object) $marketplaces->all(),
        ]);
    }
}
