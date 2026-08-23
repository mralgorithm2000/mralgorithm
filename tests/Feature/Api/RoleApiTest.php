<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_list_roles(): void
    {
        $this->actingAsUserWith('role_list');
        Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->getJson('/api/roles?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'manager')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'guard_name', 'permissions']],
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_authorized_user_can_create_a_role_with_permissions(): void
    {
        $this->actingAsUserWith('role_add');
        Permission::findOrCreate('user_list', 'web');

        $this->postJson('/api/roles', [
            'name' => 'manager',
            'permissions' => ['user_list'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'manager')
            ->assertJsonPath('data.permissions', ['user_list']);

        $this->assertDatabaseCount('permissions', 2);
    }

    public function test_authorized_user_can_show_a_role(): void
    {
        $this->actingAsUserWith('role_list');
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->getJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'manager');
    }

    public function test_authorized_user_can_update_a_role_and_sync_permissions(): void
    {
        $this->actingAsUserWith('role_edit');
        $oldPermission = Permission::findOrCreate('user_list', 'web');
        $newPermission = Permission::findOrCreate('user_edit', 'web');
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $role->givePermissionTo($oldPermission);

        $this->putJson("/api/roles/{$role->id}", [
            'name' => 'supervisor',
            'permissions' => [$newPermission->name],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'supervisor')
            ->assertJsonPath('data.permissions', ['user_edit']);
    }

    public function test_authorized_user_can_delete_a_role(): void
    {
        $this->actingAsUserWith('role_delete');
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->deleteJson("/api/roles/{$role->id}")->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedActions(): array
    {
        return [
            'index' => ['index'],
            'store' => ['store'],
            'show' => ['show'],
            'update' => ['update'],
            'destroy' => ['destroy'],
        ];
    }

    #[DataProvider('unauthorizedActions')]
    public function test_user_without_required_permission_is_forbidden(string $action): void
    {
        Sanctum::actingAs(User::factory()->create());
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $response = match ($action) {
            'index' => $this->getJson('/api/roles'),
            'store' => $this->postJson('/api/roles', ['name' => 'editor']),
            'show' => $this->getJson("/api/roles/{$role->id}"),
            'update' => $this->putJson("/api/roles/{$role->id}", ['name' => 'supervisor']),
            'destroy' => $this->deleteJson("/api/roles/{$role->id}"),
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
