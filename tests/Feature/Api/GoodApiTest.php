<?php

namespace Tests\Feature\Api;

use App\Models\Good;
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

class GoodApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/goods')->assertUnauthorized();
    }

    public function test_authorized_user_can_get_marketplace_product_ids_for_a_good(): void
    {
        $this->actingAsUserWith('good_view');
        $good = Good::create($this->goodData());
        $good->marketplaceMappings()->createMany([
            ['marketplace' => 'plati', 'marketplace_product_id' => 6020],
            ['marketplace' => 'ggsel', 'marketplace_product_id' => 6030],
        ]);

        $this->getJson("/api/goods/{$good->id}/marketplaces")
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'ggsel' => '6030',
                    'plati' => '6020',
                ],
            ]);
    }

    public function test_marketplace_product_ids_require_permission_and_can_be_empty(): void
    {
        $good = Good::create($this->goodData());
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/goods/{$good->id}/marketplaces")
            ->assertForbidden();

        $this->actingAsUserWith('good_view');

        $this->getJson("/api/goods/{$good->id}/marketplaces")
            ->assertOk()
            ->assertContent('{"data":{}}');
    }

    public function test_authorized_user_can_list_and_show_goods_with_details(): void
    {
        $this->actingAsUserWith('good_list');
        $good = Good::create($this->goodData());
        $good->details()->create($this->detailData());
        $good->marketplaceMappings()->create([
            'marketplace' => 'plati',
            'marketplace_product_id' => 123456,
        ]);

        $this->getJson('/api/goods?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Telegram Stars')
            ->assertJsonPath('data.0.type.title', 'Digital')
            ->assertJsonPath('data.0.details.0.good_key', 'region')
            ->assertJsonPath('data.0.marketplace_ids.plati', '123456')
            ->assertJsonPath('data.0.marketplace_mappings.0.marketplace', 'plati')
            ->assertJsonPath('data.0.marketplace_mappings.0.marketplace_product_id', 123456)
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson("/api/goods/{$good->id}")
            ->assertOk()
            ->assertJsonPath('data.details.0.good_value', 'Europe');
    }

    public function test_authorized_user_can_filter_paginated_goods(): void
    {
        $this->actingAsUserWith('good_list');
        Good::create($this->goodData());
        Good::create([
            ...$this->goodData(),
            'name' => 'Virtual Number',
            'type_id' => Type::firstOrCreate(['title' => 'Phone'])->id,
        ]);

        $this->getJson('/api/goods?name=Stars&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Telegram Stars')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('links.next', null);
    }

    public function test_authorized_user_can_create_update_and_delete_a_good_with_details(): void
    {
        $user = $this->actingAsUserWith('good_add');
        $user->givePermissionTo(['good_edit', 'good_delete']);

        $response = $this->postJson('/api/goods', [
            ...$this->goodData(),
            'details' => [$this->detailData()],
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Telegram Stars')
            ->assertJsonPath('data.details.0.good_key', 'region');

        $goodId = $response->json('data.id');
        $this->assertDatabaseHas('good_details', ['good_id' => $goodId, 'good_key' => 'region']);

        $this->putJson("/api/goods/{$goodId}", [
            ...$this->goodData(),
            'name' => 'Telegram Premium',
            'details' => [[
                'good_key' => 'duration',
                'good_name' => 'Duration',
                'good_value' => '12 months',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Telegram Premium')
            ->assertJsonPath('data.details.0.good_key', 'duration');

        $this->assertDatabaseMissing('good_details', ['good_id' => $goodId, 'good_key' => 'region']);

        $this->deleteJson("/api/goods/{$goodId}")->assertNoContent();
        $this->assertDatabaseMissing('goods', ['id' => $goodId]);
        $this->assertDatabaseMissing('good_details', ['good_id' => $goodId]);
    }

    public function test_validation_rejects_invalid_good_and_detail_data(): void
    {
        $this->actingAsUserWith('good_add');

        $this->postJson('/api/goods', [
            'name' => '',
            'type_id' => 999999,
            'details' => [[
                'good_key' => '',
                'good_name' => '',
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name',
            'type_id',
            'details.0.good_key',
            'details.0.good_name',
            'details.0.good_value',
        ]);
    }

    public function test_permission_and_role_seeders_grant_all_good_permissions_to_admin(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->assertTrue(Role::findByName('admin', 'web')->hasAllPermissions([
            'good_add',
            'good_edit',
            'good_list',
            'good_view',
            'good_delete',
        ]));
    }

    /** @return array<string, array{string}> */
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
        $good = Good::create($this->goodData());

        $response = match ($action) {
            'index' => $this->getJson('/api/goods'),
            'store' => $this->postJson('/api/goods', $this->goodData()),
            'show' => $this->getJson("/api/goods/{$good->id}"),
            'update' => $this->putJson("/api/goods/{$good->id}", $this->goodData()),
            'destroy' => $this->deleteJson("/api/goods/{$good->id}"),
        };

        $response->assertForbidden();
    }

    /** @return array<string, string|int> */
    private function goodData(): array
    {
        return [
            'name' => 'Telegram Stars',
            'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
        ];
    }

    /** @return array<string, string> */
    private function detailData(): array
    {
        return [
            'good_key' => 'region',
            'good_name' => 'Region',
            'good_value' => 'Europe',
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
