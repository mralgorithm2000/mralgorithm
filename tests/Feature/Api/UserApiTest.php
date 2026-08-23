<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_authorized_user_can_list_users_with_pagination(): void
    {
        $this->actingAsUserWith('user_list');
        User::factory()->create(['name' => 'Listed User']);

        $this->getJson('/api/users?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'created_at', 'updated_at']],
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_authorized_user_can_create_a_user_with_a_hashed_password(): void
    {
        $this->actingAsUserWith('user_add');

        $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
    }

    public function test_authorized_user_can_show_a_user_without_exposing_password(): void
    {
        $this->actingAsUserWith('user_list');
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_authorized_user_can_update_a_user_and_keep_the_same_email(): void
    {
        $this->actingAsUserWith('user_edit');
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated User',
            'email' => $user->email,
            'password' => 'updated-secret',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated User')
            ->assertJsonMissingPath('data.password');

        $this->assertTrue(Hash::check('updated-secret', $user->fresh()->password));
    }

    public function test_authorized_user_can_delete_a_user(): void
    {
        $this->actingAsUserWith('user_delete');
        $user = User::factory()->create();

        $this->deleteJson("/api/users/{$user->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_store_validation_rejects_invalid_and_duplicate_values(): void
    {
        $this->actingAsUserWith('user_add');
        $existing = User::factory()->create();

        $this->postJson('/api/users', [
            'name' => '',
            'email' => $existing->email,
            'password' => 'short',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_update_validation_rejects_another_users_email(): void
    {
        $this->actingAsUserWith('user_edit');
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $other->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenActions(): array
    {
        return [
            'index' => ['index'],
            'store' => ['store'],
            'show' => ['show'],
            'update' => ['update'],
            'destroy' => ['destroy'],
        ];
    }

    #[DataProvider('forbiddenActions')]
    public function test_user_without_required_permission_is_forbidden(string $action): void
    {
        Sanctum::actingAs(User::factory()->create());
        $user = User::factory()->create();

        $response = match ($action) {
            'index' => $this->getJson('/api/users'),
            'store' => $this->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'secret123',
            ]),
            'show' => $this->getJson("/api/users/{$user->id}"),
            'update' => $this->putJson("/api/users/{$user->id}", [
                'name' => 'Updated User',
                'email' => $user->email,
            ]),
            'destroy' => $this->deleteJson("/api/users/{$user->id}"),
        };

        $response->assertForbidden();
    }

    private function actingAsUserWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        Sanctum::actingAs($user);

        return $user;
    }
}
