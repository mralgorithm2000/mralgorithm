<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_their_profile(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->put('/api/profile', [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'password' => 'new-password',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Updated User')
            ->assertJsonPath('user.email', 'updated@example.com')
            ->assertJsonMissingPath('user.password');

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->putJson('/api/profile', ['name' => 'Updated User'])
            ->assertUnauthorized();
    }

    public function test_current_email_is_allowed_but_another_users_email_is_not(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/profile', ['email' => $user->email])->assertOk();

        $this->putJson('/api/profile', ['email' => $otherUser->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_cannot_change_their_roles_through_the_profile_endpoint(): void
    {
        $user = User::factory()->create();
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($userRole);
        Sanctum::actingAs($user);

        $this->putJson('/api/profile', ['roles' => ['admin']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles');

        $this->assertTrue($user->fresh()->hasExactRoles('user'));
    }
}
