<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParameterRequest;
use App\Http\Requests\UpdateParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Models\Good;
use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ParameterOption',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parameter_id', type: 'integer', example: 1),
        new OA\Property(property: 'option_name', type: 'string', example: 'Europe'),
        new OA\Property(property: 'option_value', type: 'string', example: 'EU'),
        new OA\Property(property: 'operator', type: 'string', enum: ['+', '-', '%'], nullable: true, example: '+'),
        new OA\Property(property: 'additional_price', type: 'number', format: 'float', nullable: true, example: 2.5),
        new OA\Property(property: 'original_price', type: 'number', format: 'double', minimum: 0, maximum: 999999999999.999999, nullable: true, example: 10.5),
        new OA\Property(property: 'selling_price', type: 'number', format: 'double', minimum: 0, maximum: 999999999999.999999, nullable: true, example: 12.5),
        new OA\Property(property: 'supplier_name', type: 'string', nullable: true, example: 'Example Supplier'),
        new OA\Property(property: 'supplier_product_id', type: 'string', maxLength: 255, nullable: true, example: 'SUP-EU-001'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Parameter',
    required: ['title', 'type', 'parameter_key'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'goods_id', type: 'integer', example: 1),
        new OA\Property(property: 'parameter_key', type: 'string', maxLength: 255, example: 'region'),
        new OA\Property(property: 'title', type: 'string', example: 'Region'),
        new OA\Property(property: 'type', type: 'string', enum: ['text', 'dropdown', 'radio_button', 'checkbox', 'multiline_textarea'], example: 'dropdown'),
        new OA\Property(property: 'is_main', type: 'boolean', example: true),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(ref: '#/components/schemas/ParameterOption')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class GoodParameterController extends Controller
{
    #[OA\Get(
        path: '/api/goods/{good}/parameters',
        operationId: 'listGoodParameters',
        summary: 'List available parameters in the context of a good',
        tags: ['Goods'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Parameters with their available options',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Parameter')),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
        ],
    )]
    public function index(Good $good): AnonymousResourceCollection
    {
        Gate::authorize('manageParameters', $good);

        return ParameterResource::collection(
            Parameter::query()
                ->where('goods_id', $good->id)
                ->with('options.supplier')
                ->orderBy('id')
                ->get(),
        );
    }

    #[OA\Post(
        path: '/api/goods/{good}/parameters',
        operationId: 'createGoodParameter',
        summary: 'Create one parameter in the context of a good',
        tags: ['Goods'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'type', 'parameter_key'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Region'),
                    new OA\Property(property: 'parameter_key', type: 'string', maxLength: 255, example: 'region'),
                    new OA\Property(property: 'type', type: 'string', enum: ['text', 'dropdown', 'radio_button', 'checkbox', 'multiline_textarea'], example: 'dropdown'),
                    new OA\Property(property: 'is_main', type: 'boolean', example: true, nullable: true),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Parameter created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Parameter'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreParameterRequest $request, Good $good): JsonResponse
    {
        Gate::authorize('manageParameters', $good);

        $data = $request->validated();
        $data['goods_id'] = $good->id;
        $parameter = Parameter::create($data);

        return (new ParameterResource($parameter->load('options')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/goods/{good}/parameters/{parameter}',
        operationId: 'updateGoodParameter',
        summary: 'Update a parameter in the context of a good',
        tags: ['Goods'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'good', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'parameter', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'type', 'parameter_key'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Region'),
                    new OA\Property(property: 'parameter_key', type: 'string', maxLength: 255, example: 'region'),
                    new OA\Property(property: 'type', type: 'string', enum: ['text', 'dropdown', 'radio_button', 'checkbox', 'multiline_textarea'], example: 'dropdown'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Parameter updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Parameter'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Good or parameter not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateParameterRequest $request, Good $good, Parameter $parameter): JsonResponse
    {
        Gate::authorize('manageParameters', $good);

        if ($parameter->goods_id !== $good->id) {
            abort(404);
        }

        $parameter->update($request->validated());

        return (new ParameterResource($parameter->load('options')))
            ->response()
            ->setStatusCode(200);
    }
}
