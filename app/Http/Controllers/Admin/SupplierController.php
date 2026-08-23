<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Supplier',
    required: ['title', 'website_url', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, readOnly: true),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Supplier Ltd'),
        new OA\Property(property: 'website_url', type: 'string', format: 'uri', example: 'https://supplier.example'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
    type: 'object',
)]
class SupplierController extends Controller
{
    #[OA\Get(
        path: '/api/suppliers/all',
        operationId: 'allSuppliers',
        summary: 'List all suppliers for selection controls',
        tags: ['Suppliers'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All suppliers as value/title options',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'suppliers',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'value', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Supplier Ltd'),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function all(): JsonResponse
    {
        Gate::authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Supplier $supplier): array => [
                'value' => $supplier->id,
                'title' => $supplier->title,
            ]);

        return response()->json(['suppliers' => $suppliers]);
    }

    #[OA\Get(
        path: '/api/suppliers', operationId: 'listSuppliers', summary: 'List suppliers', tags: ['Suppliers'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated suppliers list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Supplier::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return SupplierResource::collection(
            Supplier::query()->orderByDesc('id')->paginate($perPage),
        );
    }

    #[OA\Post(
        path: '/api/suppliers', operationId: 'createSupplier', summary: 'Create a supplier', tags: ['Suppliers'], security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Supplier')),
        responses: [
            new OA\Response(response: 201, description: 'Supplier created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        Gate::authorize('create', Supplier::class);

        return (new SupplierResource(Supplier::create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/suppliers/{supplier}', operationId: 'showSupplier', summary: 'Show a supplier', tags: ['Suppliers'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Supplier details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Supplier not found'),
        ],
    )]
    public function show(Supplier $supplier): SupplierResource
    {
        Gate::authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    #[OA\Put(
        path: '/api/suppliers/{supplier}', operationId: 'updateSupplier', summary: 'Update a supplier', tags: ['Suppliers'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Supplier')),
        responses: [
            new OA\Response(response: 200, description: 'Supplier updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Supplier not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        Gate::authorize('update', $supplier);
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    #[OA\Delete(
        path: '/api/suppliers/{supplier}', operationId: 'deleteSupplier', summary: 'Delete a supplier', tags: ['Suppliers'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Supplier deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Supplier not found'),
        ],
    )]
    public function destroy(Supplier $supplier): Response
    {
        Gate::authorize('delete', $supplier);
        $supplier->delete();

        return response()->noContent();
    }
}
