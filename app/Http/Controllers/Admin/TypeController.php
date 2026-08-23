<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTypeRequest;
use App\Http\Requests\UpdateTypeRequest;
use App\Http\Resources\TypeItemResource;
use App\Http\Resources\TypeResource;
use App\Models\Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TypeItem',
    required: ['type_key', 'type_name', 'type'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type_key', type: 'string', example: 'country'),
        new OA\Property(property: 'type_name', type: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', enum: ['text', 'dropdown', 'multiple_choice'], example: 'dropdown'),
        new OA\Property(property: 'options', type: 'array', nullable: true, items: new OA\Items(type: 'string'), example: ['US', 'GB']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Type',
    required: ['title'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Account region'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TypeItem')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class TypeController extends Controller
{
    #[OA\Get(
        path: '/api/types/all',
        operationId: 'allTypes',
        summary: 'List all types for selection controls',
        tags: ['Types'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All types as value/title options',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'types',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'value', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Account region'),
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
        Gate::authorize('viewAny', Type::class);

        $types = Type::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Type $type): array => [
                'value' => $type->id,
                'title' => $type->title,
            ]);

        return response()->json(['types' => $types]);
    }

    #[OA\Get(
        path: '/api/types', operationId: 'listTypes', summary: 'List types', tags: ['Types'], security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated types list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Type::class);
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return TypeResource::collection(
            Type::query()->with('items')->orderByDesc('id')->paginate($perPage),
        );
    }

    #[OA\Post(
        path: '/api/types', operationId: 'createType', summary: 'Create a type', tags: ['Types'], security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Account region'),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TypeItem')),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Type created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreTypeRequest $request): JsonResponse
    {
        Gate::authorize('create', Type::class);
        $validated = $request->validated();

        $type = DB::transaction(function () use ($validated): Type {
            $type = Type::create(Arr::only($validated, ['title']));
            $type->items()->createMany($validated['items'] ?? []);

            return $type;
        });

        return (new TypeResource($type->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/types/{type}', operationId: 'showType', summary: 'Show a type', tags: ['Types'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Type details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Type not found'),
        ],
    )]
    public function show(Type $type): TypeResource
    {
        Gate::authorize('view', $type);

        return new TypeResource($type->load('items'));
    }

    #[OA\Get(
        path: '/api/types/{type}/items',
        operationId: 'listTypeItems',
        summary: 'List the items belonging to a type',
        tags: ['Types'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'type',
                description: 'Type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Type items',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TypeItem'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Type not found'),
        ],
    )]
    public function items(Type $type): AnonymousResourceCollection
    {
        Gate::authorize('view', $type);

        return TypeItemResource::collection($type->items()->get());
    }

    #[OA\Put(
        path: '/api/types/{type}', operationId: 'updateType', summary: 'Update a type', tags: ['Types'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Account region'),
                new OA\Property(property: 'items', description: 'Replaces all existing items when supplied.', type: 'array', items: new OA\Items(ref: '#/components/schemas/TypeItem')),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Type updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Type not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateTypeRequest $request, Type $type): TypeResource
    {
        Gate::authorize('update', $type);
        $validated = $request->validated();

        DB::transaction(function () use ($type, $validated): void {
            $type->update(Arr::only($validated, ['title']));

            if (array_key_exists('items', $validated)) {
                $type->items()->delete();
                $type->items()->createMany($validated['items']);
            }
        });

        return new TypeResource($type->load('items'));
    }

    #[OA\Delete(
        path: '/api/types/{type}', operationId: 'deleteType', summary: 'Delete a type', tags: ['Types'], security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Type deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Type not found'),
        ],
    )]
    public function destroy(Type $type): Response
    {
        Gate::authorize('delete', $type);
        $type->delete();

        return response()->noContent();
    }
}
