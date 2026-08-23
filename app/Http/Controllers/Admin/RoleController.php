<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'manager'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'web'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['role_list']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class RoleController extends Controller
{
    #[OA\Get(
        path: '/api/roles',
        operationId: 'listRoles',
        summary: 'List roles',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated roles list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return RoleResource::collection(
            Role::query()
                ->where('guard_name', 'web')
                ->with('permissions')
                ->orderByDesc('id')
                ->paginate($perPage),
        );
    }

    #[OA\Post(
        path: '/api/roles',
        operationId: 'createRole',
        summary: 'Create a role',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'manager'),
                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['role_list']),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Role created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        Gate::authorize('create', Role::class);

        $role = DB::transaction(function () use ($request): Role {
            $role = Role::create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($request->validated('permissions', []));

            return $role;
        });

        return (new RoleResource($role->load('permissions')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/roles/{role}',
        operationId: 'showRole',
        summary: 'Show a role',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Role details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role not found'),
        ],
    )]
    public function show(Role $role): RoleResource
    {
        Gate::authorize('view', $role);

        return new RoleResource($role->load('permissions'));
    }

    #[OA\Put(
        path: '/api/roles/{role}',
        operationId: 'updateRole',
        summary: 'Update a role',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'manager'),
                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['role_list', 'user_list']),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Role updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        Gate::authorize('update', $role);

        DB::transaction(function () use ($request, $role): void {
            $role->update(Arr::only($request->validated(), ['name']));

            if ($request->has('permissions')) {
                $role->syncPermissions($request->validated('permissions'));
            }
        });

        return new RoleResource($role->load('permissions'));
    }

    #[OA\Delete(
        path: '/api/roles/{role}',
        operationId: 'deleteRole',
        summary: 'Delete a role',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Role deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role not found'),
        ],
    )]
    public function destroy(Role $role): Response
    {
        Gate::authorize('delete', $role);

        $role->delete();

        return response()->noContent();
    }
}
