<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Put(
        path: '/api/profile',
        operationId: 'updateProfile',
        summary: 'Update the authenticated user profile',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Administrator'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Administrator'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                                new OA\Property(property: 'avatar', type: 'string', nullable: true, example: 'http://localhost/storage/avatars/avatar.jpg'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attributes = Arr::only($request->validated(), ['name', 'email', 'password']);
        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $attributes['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($attributes);

        if (isset($attributes['avatar']) && $oldAvatar !== null) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                ...$user->only(['id', 'name', 'email']),
                'avatar' => $user->avatar === null
                    ? null
                    : Storage::disk('public')->url($user->avatar),
            ],
        ]);
    }
}
