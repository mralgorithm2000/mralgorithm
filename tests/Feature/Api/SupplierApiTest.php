<?php

namespace Tests\Feature\Api;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $supplier = Supplier::create($this->supplierData());

        $this->getJson('/api/suppliers')->assertUnauthorized();
        $this->getJson('/api/suppliers/all')->assertUnauthorized();
        $this->getJson("/api/suppliers/{$supplier->id}")->assertUnauthorized();
        $this->postJson('/api/suppliers', $this->supplierData())->assertUnauthorized();
        $this->putJson("/api/suppliers/{$supplier->id}", $this->supplierData())->assertUnauthorized();
        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertUnauthorized();
    }

    public function test_authorized_user_can_get_all_suppliers_as_options(): void
    {
        $this->actingAsUserWith('supplier_list');
        $second = Supplier::create([
            'title' => 'Zulu Supplier',
            'website_url' => 'https://zulu.example',
            'status' => 'active',
        ]);
        $first = Supplier::create([
            'title' => 'Alpha Supplier',
            'website_url' => 'https://alpha.example',
            'status' => 'active',
        ]);
        Supplier::create([
            'title' => 'Inactive Supplier',
            'website_url' => 'https://inactive.example',
            'status' => 'inactive',
        ]);

        $this->getJson('/api/suppliers/all')
            ->assertOk()
            ->assertExactJson([
                'suppliers' => [
                    ['value' => $first->id, 'title' => 'Alpha Supplier'],
                    ['value' => $second->id, 'title' => 'Zulu Supplier'],
                ],
            ]);
    }

    public function test_authorized_user_can_list_and_show_suppliers(): void
    {
        $this->actingAsUserWith('supplier_list');
        $supplier = Supplier::create($this->supplierData());

        $this->getJson('/api/suppliers?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Supplier Ltd')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson("/api/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertJsonPath('data.website_url', 'https://supplier.example');
    }

    public function test_authorized_users_can_create_update_and_delete_a_supplier(): void
    {
        $user = $this->actingAsUserWith('supplier_add');
        $user->givePermissionTo(['supplier_edit', 'supplier_delete']);

        $response = $this->postJson('/api/suppliers', $this->supplierData())
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $supplierId = $response->json('data.id');

        $this->putJson("/api/suppliers/{$supplierId}", [
            'title' => 'Updated Supplier',
            'website_url' => 'https://updated.example',
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Supplier')
            ->assertJsonPath('data.status', 'inactive');

        $this->deleteJson("/api/suppliers/{$supplierId}")->assertNoContent();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplierId]);
    }

    public function test_supplier_validation_rejects_invalid_data(): void
    {
        $this->actingAsUserWith('supplier_add');

        $this->postJson('/api/suppliers', [
            'title' => '',
            'website_url' => 'not-a-url',
            'status' => 'disabled',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'title',
            'website_url',
            'status',
        ]);
    }

    public function test_permission_and_role_seeders_grant_supplier_permissions_to_admin(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->assertTrue(Role::findByName('admin', 'web')->hasAllPermissions([
            'supplier_add',
            'supplier_edit',
            'supplier_list',
            'supplier_delete',
        ]));
    }

    /** @return array<string, array{string}> */
    public static function forbiddenActions(): array
    {
        return [
            'all' => ['all'],
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
        $supplier = Supplier::create($this->supplierData());

        $response = match ($action) {
            'all' => $this->getJson('/api/suppliers/all'),
            'index' => $this->getJson('/api/suppliers'),
            'store' => $this->postJson('/api/suppliers', $this->supplierData()),
            'show' => $this->getJson("/api/suppliers/{$supplier->id}"),
            'update' => $this->putJson("/api/suppliers/{$supplier->id}", $this->supplierData()),
            'destroy' => $this->deleteJson("/api/suppliers/{$supplier->id}"),
        };

        $response->assertForbidden();
    }

    /** @return array<string, string> */
    private function supplierData(): array
    {
        return [
            'title' => 'Supplier Ltd',
            'website_url' => 'https://supplier.example',
            'status' => 'active',
        ];
    }

    private function actingAsUserWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        Sanctum::actingAs($user);

        return $user;
    }
}
