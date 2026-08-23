<?php

namespace Tests\Feature\Api;

use App\Models\Type;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/types')->assertUnauthorized();
        $this->getJson('/api/types/all')->assertUnauthorized();
        $this->getJson('/api/types/1/items')->assertUnauthorized();
    }

    public function test_authorized_user_can_get_all_types_as_options(): void
    {
        $this->actingAsUserWith('list_types');
        $first = Type::create(['title' => 'Account region']);
        $second = Type::create(['title' => 'Product category']);

        $this->getJson('/api/types/all')
            ->assertOk()
            ->assertExactJson([
                'types' => [
                    ['value' => $first->id, 'title' => 'Account region'],
                    ['value' => $second->id, 'title' => 'Product category'],
                ],
            ]);
    }

    public function test_authorized_user_can_list_and_show_types_with_items(): void
    {
        $this->actingAsUserWith('list_types');
        $type = Type::create(['title' => 'Account region']);
        $type->items()->create([
            'type_key' => 'country',
            'type_name' => 'Country',
            'type' => 'dropdown',
            'options' => ['US', 'GB'],
        ]);

        $this->getJson('/api/types?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Account region')
            ->assertJsonPath('data.0.items.0.type_key', 'country')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson("/api/types/{$type->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.options', ['US', 'GB']);
    }

    public function test_authorized_user_can_get_items_for_a_type(): void
    {
        $this->actingAsUserWith('list_types');
        $type = Type::create(['title' => 'Account region']);
        $otherType = Type::create(['title' => 'Product category']);
        $type->items()->create([
            'type_key' => 'country',
            'type_name' => 'Country',
            'type' => 'dropdown',
            'options' => ['US', 'GB'],
        ]);
        $otherType->items()->create([
            'type_key' => 'category',
            'type_name' => 'Category',
            'type' => 'text',
        ]);

        $this->getJson("/api/types/{$type->id}/items")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type_key', 'country')
            ->assertJsonPath('data.0.options', ['US', 'GB']);
    }

    public function test_getting_items_for_an_unknown_type_returns_not_found(): void
    {
        $this->actingAsUserWith('list_types');

        $this->getJson('/api/types/999/items')->assertNotFound();
    }

    public function test_authorized_user_can_create_a_type_with_items(): void
    {
        $this->actingAsUserWith('add_type');

        $this->postJson('/api/types', [
            'title' => 'Account region',
            'items' => [[
                'type_key' => 'country',
                'type_name' => 'Country',
                'type' => 'dropdown',
                'options' => ['US', 'GB'],
            ]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Account region')
            ->assertJsonPath('data.items.0.type', 'dropdown')
            ->assertJsonPath('data.items.0.type_key', 'country');

        $this->assertDatabaseHas('type_items', [
            'type_key' => 'country',
            'type_name' => 'Country',
        ]);
    }

    public function test_authorized_user_can_update_a_type_and_replace_its_items(): void
    {
        $this->actingAsUserWith('edit_type');
        $type = Type::create(['title' => 'Region']);
        $type->items()->create(['type_key' => 'country', 'type_name' => 'Country', 'type' => 'text']);

        $this->putJson("/api/types/{$type->id}", [
            'title' => 'Account region',
            'items' => [[
                'type_key' => 'region',
                'type_name' => 'Regions',
                'type' => 'multiple_choice',
                'options' => ['Europe', 'Asia'],
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.items.0.type', 'multiple_choice')
            ->assertJsonPath('data.items.0.type_key', 'region');

        $this->assertDatabaseMissing('type_items', ['type_key' => 'country']);
    }

    public function test_authorized_user_can_delete_a_type_and_its_items(): void
    {
        $this->actingAsUserWith('delete_type');
        $type = Type::create(['title' => 'Region']);
        $item = $type->items()->create(['type_key' => 'country', 'type_name' => 'Country', 'type' => 'text']);

        $this->deleteJson("/api/types/{$type->id}")->assertNoContent();

        $this->assertDatabaseMissing('types', ['id' => $type->id]);
        $this->assertDatabaseMissing('type_items', ['id' => $item->id]);
    }

    public function test_type_validation_rejects_invalid_data_and_duplicate_keys(): void
    {
        $this->actingAsUserWith('add_type');
        $type = Type::create(['title' => 'Existing']);
        $type->items()->create(['type_key' => 'country', 'type_name' => 'Country', 'type' => 'text']);

        $this->postJson('/api/types', [
            'title' => '',
            'items' => [[
                'type_key' => 'country',
                'type_name' => '',
                'type' => 'invalid',
                'options' => 'invalid',
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'items.0.type',
                'items.0.type_key',
                'items.0.type_name',
                'items.0.options',
            ]);
    }

    public function test_permission_and_role_seeders_grant_all_type_permissions_to_admin(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = Role::findByName('admin', 'web');

        $this->assertTrue($admin->hasAllPermissions([
            'add_type',
            'edit_type',
            'list_types',
            'delete_type',
        ]));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenActions(): array
    {
        return [
            'all' => ['all'],
            'index' => ['index'],
            'store' => ['store'],
            'show' => ['show'],
            'items' => ['items'],
            'update' => ['update'],
            'destroy' => ['destroy'],
        ];
    }

    #[DataProvider('forbiddenActions')]
    public function test_user_without_required_permission_is_forbidden(string $action): void
    {
        Sanctum::actingAs(User::factory()->create());
        $type = Type::create(['title' => 'Region']);

        $response = match ($action) {
            'all' => $this->getJson('/api/types/all'),
            'index' => $this->getJson('/api/types'),
            'store' => $this->postJson('/api/types', ['title' => 'Region']),
            'show' => $this->getJson("/api/types/{$type->id}"),
            'items' => $this->getJson("/api/types/{$type->id}/items"),
            'update' => $this->putJson("/api/types/{$type->id}", ['title' => 'Region']),
            'destroy' => $this->deleteJson("/api/types/{$type->id}"),
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
