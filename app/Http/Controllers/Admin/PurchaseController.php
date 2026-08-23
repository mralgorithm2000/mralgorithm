<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\PurchaseShowResource;
use App\Models\Purchase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Purchase',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'marketplace', type: 'string', example: 'Plati'),
        new OA\Property(property: 'marketplace_order_id', type: 'string', example: '12345'),
        new OA\Property(property: 'goods_id', type: 'integer', example: 1),
        new OA\Property(property: 'sold_price', type: 'string', example: '12.500000'),
        new OA\Property(property: 'cost_price', type: 'string', example: '10.000000'),
        new OA\Property(property: 'marketplace_fee', type: 'string', example: '1.250000'),
        new OA\Property(property: 'refunded_amount', type: 'string', example: '0.000000'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(
            property: 'good',
            properties: [new OA\Property(property: 'name', type: 'string', example: 'Telegram Stars')],
            type: 'object',
            readOnly: true,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PurchaseShow',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'marketplace', type: 'string', example: 'Plati'),
        new OA\Property(property: 'marketplace_order_id', type: 'string', example: '12345'),
        new OA\Property(property: 'goods_id', type: 'integer', example: 1),
        new OA\Property(property: 'sold_price', type: 'string', example: '12.500000'),
        new OA\Property(property: 'cost_price', type: 'string', example: '10.000000'),
        new OA\Property(property: 'marketplace_fee', type: 'string', example: '1.250000'),
        new OA\Property(property: 'refunded_amount', type: 'string', example: '0.000000'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'good', ref: '#/components/schemas/Good'),
        new OA\Property(
            property: 'orders',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'purchase_id', type: 'integer', example: 1),
                    new OA\Property(property: 'supplier_order_id', type: 'string', example: 'SUP-12345'),
                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                    new OA\Property(property: 'sold_price', type: 'string', example: '12.500000'),
                    new OA\Property(property: 'cost_price', type: 'string', example: '10.000000'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'order_details', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderDetail')),
                ],
                type: 'object',
            ),
        ),
    ],
    type: 'object',
)]
class PurchaseController extends Controller
{
    #[OA\Get(
        path: '/api/purchases', operationId: 'listPurchases', summary: 'List purchases', tags: ['Purchases'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'marketplace', description: 'Filter by marketplace.', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'marketplace_order_id', description: 'Filter by marketplace order ID.', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'goods', description: 'Filter by related Good name (partial match).', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated purchases list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Purchase::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        $query = Purchase::query()->with('good');

        foreach (['marketplace', 'marketplace_order_id'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request()->string($field)->trim());
            }
        }

        if (request()->filled('goods')) {
            $goods = request()->string('goods')->trim();
            $query->whereHas('good', fn ($query) => $query->where('name', 'like', "%{$goods}%"));
        }

        return PurchaseResource::collection($query->orderByDesc('id')->paginate($perPage)->withQueryString());
    }

    #[OA\Get(
        path: '/api/purchases/{purchase}', operationId: 'showPurchase', summary: 'Show a purchase with its Good and orders', tags: ['Purchases'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'purchase', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Purchase details including the full Good, Good details, and related orders',
                content: new OA\JsonContent(ref: '#/components/schemas/PurchaseShow'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Purchase not found'),
        ],
    )]
    public function show(Purchase $purchase): PurchaseShowResource
    {
        Gate::authorize('view', $purchase);

        return new PurchaseShowResource($purchase->load([
            'good.details',
            'good.marketplaceMappings',
            'good.type',
            'orders.orderDetails',
        ]));
    }
}
