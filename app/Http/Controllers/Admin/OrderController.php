<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderShowResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderDetail',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'order_detail_key', type: 'string', example: 'service'),
        new OA\Property(property: 'order_detail_name', type: 'string', example: 'Service'),
        new OA\Property(property: 'order_detail_value', type: 'string', example: 'Telegram'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Order',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'purchase_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'supplier_order_id', type: 'string', nullable: true, example: 'SUP-12345'),
        new OA\Property(property: 'good_name', type: 'string', nullable: true, example: 'Telegram Stars'),
        new OA\Property(property: 'marketplace', type: 'string', nullable: true, example: 'Plati'),
        new OA\Property(property: 'marketplace_order_id', type: 'string', nullable: true, example: '12345'),
        new OA\Property(property: 'status', type: 'string', nullable: true, example: 'completed'),
        new OA\Property(property: 'sold_price', type: 'string', example: '12.500000'),
        new OA\Property(property: 'cost_price', type: 'string', example: '10.000000'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'order_details', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderDetail'), readOnly: true),
        new OA\Property(
            property: 'purchase',
            description: 'Full Purchase and Good information. Included on the show endpoint only.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'marketplace', type: 'string', example: 'Plati'),
                new OA\Property(property: 'marketplace_order_id', type: 'string', example: '12345'),
                new OA\Property(property: 'goods_id', type: 'integer', example: 1),
                new OA\Property(property: 'sold_price', type: 'string', example: '12.500000'),
                new OA\Property(property: 'cost_price', type: 'string', example: '10.000000'),
                new OA\Property(property: 'marketplace_fee', type: 'string', example: '1.250000'),
                new OA\Property(property: 'refunded_amount', type: 'string', example: '0.000000'),
                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'good', ref: '#/components/schemas/Good'),
            ],
            type: 'object',
            nullable: true,
            readOnly: true,
        ),
    ],
    type: 'object',
)]
class OrderController extends Controller
{
    #[OA\Get(
        path: '/api/orders', operationId: 'listOrders', summary: 'List orders', tags: ['Orders'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'purchase_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'supplier_order_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'good_name', description: 'Filter by Good name (partial match).', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'marketplace', description: 'Filter by marketplace.', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'marketplace_order_id', description: 'Filter by marketplace order ID.', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated orders list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Order::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);
        $query = Order::query()->with('purchase.good.marketplaceMappings');

        if (request()->filled('purchase_id')) {
            $query->where('purchase_id', request()->integer('purchase_id'));
        }

        foreach (['supplier_order_id', 'status'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request()->string($field)->trim());
            }
        }

        if (request()->filled('good_name')) {
            $goodName = request()->string('good_name')->trim();
            $query->whereHas('purchase.good', fn ($query) => $query->where('name', 'like', "%{$goodName}%"));
        }

        if (request()->filled('marketplace')) {
            $marketplace = request()->string('marketplace')->trim();
            $query->whereHas('purchase', fn ($query) => $query->where('marketplace', $marketplace));
        }

        if (request()->filled('marketplace_order_id')) {
            $marketplaceOrderId = request()->string('marketplace_order_id')->trim();
            $query->whereHas(
                'purchase',
                fn ($query) => $query->where('marketplace_order_id', $marketplaceOrderId),
            );
        }

        return OrderResource::collection($query->orderByDesc('id')->paginate($perPage)->withQueryString());
    }

    #[OA\Get(
        path: '/api/orders/{order}', operationId: 'showOrder', summary: 'Show an order', tags: ['Orders'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'order', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Order details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order not found'),
        ],
    )]
    public function show(Order $order): OrderShowResource
    {
        Gate::authorize('view', $order);

        return new OrderShowResource($order->load([
            'purchase.good.details',
            'purchase.good.marketplaceMappings',
            'purchase.good.type',
            'orderDetails',
        ]));
    }
}
