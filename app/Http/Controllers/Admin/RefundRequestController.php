<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseStatus;
use App\Enums\RefundRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRefundRequestStatusRequest;
use App\Http\Resources\RefundRequestResource;
use App\Http\Resources\RefundRequestShowResource;
use App\Models\RefundRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RefundRequest',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'], example: 'pending'),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'The service was not delivered.'),
        new OA\Property(property: 'requested_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'reviewed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'admin_note', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'purchase',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'marketplace_order_id', type: 'string', example: '12345'),
                new OA\Property(property: 'marketplace', type: 'string', example: 'Plati'),
                new OA\Property(
                    property: 'good',
                    properties: [new OA\Property(property: 'name', type: 'string', example: 'Telegram Stars')],
                    type: 'object',
                ),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RefundRequestShow',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/RefundRequest'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'purchase',
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
                    ],
                    type: 'object',
                ),
                new OA\Property(
                    property: 'admin',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Refund Admin'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object',
                    nullable: true,
                ),
            ],
            type: 'object',
        ),
    ],
)]
class RefundRequestController extends Controller
{
    #[OA\Get(
        path: '/api/refund-requests', operationId: 'listRefundRequests', summary: 'List refund requests', tags: ['Refund Requests'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'status', description: 'Filter by refund request status.', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'])),
            new OA\Parameter(name: 'purchase_id', description: 'Filter by purchase ID.', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'marketplace_order_id', description: 'Filter by the related purchase marketplace order ID.', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated refund request list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RefundRequest::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);
        $query = RefundRequest::query()->with('purchase.good');

        if (request()->filled('status')) {
            $query->where('status', request()->string('status')->trim());
        }

        if (request()->filled('purchase_id')) {
            $query->where('purchase_id', request()->integer('purchase_id'));
        }

        if (request()->filled('marketplace_order_id')) {
            $marketplaceOrderId = request()->string('marketplace_order_id')->trim();
            $query->whereHas(
                'purchase',
                fn ($query) => $query->where('marketplace_order_id', $marketplaceOrderId),
            );
        }

        return RefundRequestResource::collection(
            $query
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
        );
    }

    #[OA\Get(
        path: '/api/refund-requests/{refundRequest}', operationId: 'showRefundRequest', summary: 'Show a refund request', tags: ['Refund Requests'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'refundRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Full refund request, purchase, Good, marketplace mappings, and admin details',
                content: new OA\JsonContent(ref: '#/components/schemas/RefundRequestShow'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Refund request not found'),
        ],
    )]
    public function show(RefundRequest $refundRequest): RefundRequestShowResource
    {
        Gate::authorize('view', $refundRequest);

        return new RefundRequestShowResource($refundRequest->load([
            'purchase.good.details',
            'purchase.good.marketplaceMappings',
            'purchase.good.type',
            'admin',
        ]));
    }

    #[OA\Put(
        path: '/api/refund-requests/{refundRequest}/status', operationId: 'updateRefundRequestStatus', summary: 'Update a refund request status', description: 'Completed refunds set the related purchase status to refunded. Rejected refunds set it to completed. Pending and approved refunds leave the purchase status unchanged.', tags: ['Refund Requests'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'refundRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'], example: 'approved'),
                    new OA\Property(property: 'admin_note', type: 'string', nullable: true, example: 'Approved by support.'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund request status updated', content: new OA\JsonContent(ref: '#/components/schemas/RefundRequest')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Refund request not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function updateStatus(
        UpdateRefundRequestStatusRequest $request,
        RefundRequest $refundRequest,
    ): RefundRequestResource {
        Gate::authorize('updateStatus', $refundRequest);
        $validated = $request->validated();
        $status = RefundRequestStatus::from($validated['status']);
        $reviewedAt = $status === RefundRequestStatus::PENDING ? null : now();

        DB::transaction(function () use ($refundRequest, $status, $validated, $request, $reviewedAt): void {
            $refundRequest->update([
                'status' => $status->value,
                'admin_note' => $validated['admin_note'] ?? null,
                'admin_id' => $request->user()->id,
                'reviewed_at' => $reviewedAt,
                'completed_at' => $status === RefundRequestStatus::COMPLETED ? now() : null,
            ]);

            $purchaseStatus = match ($status) {
                RefundRequestStatus::COMPLETED => PurchaseStatus::REFUNDED,
                RefundRequestStatus::REJECTED => PurchaseStatus::COMPLETED,
                default => null,
            };

            if ($purchaseStatus !== null) {
                $refundRequest->purchase()->update(['status' => $purchaseStatus->value]);
            }
        });

        return new RefundRequestResource($refundRequest->load('purchase.good'));
    }
}
