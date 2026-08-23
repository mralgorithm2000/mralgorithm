<?php

namespace Tests\Feature\Api;

use App\Models\Good;
use App\Models\PlatiTokens;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoodDigisellerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_requires_authentication(): void
    {
        $good = $this->createGood();

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", $this->fixedPayload())
            ->assertUnauthorized();
        $this->postJson("/api/goods/{$good->id}/digiseller/variable", $this->variablePayload())
            ->assertUnauthorized();
    }

    public function test_good_must_exist(): void
    {
        $this->actingAsPublisher();

        $this->postJson('/api/goods/999999/digiseller/fixed', $this->fixedPayload())
            ->assertNotFound();
    }

    public function test_publishing_requires_good_add_plati_detail_permission(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $good = $this->createGood();
        Http::fake();

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", $this->fixedPayload())
            ->assertForbidden();
        $this->postJson("/api/goods/{$good->id}/digiseller/variable", $this->variablePayload())
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_product_data_is_validated(): void
    {
        $this->actingAsPublisher();
        $good = $this->createGood();
        Http::fake();

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", [
            'name' => '',
            'description' => '',
            'add_info' => '',
            'plati_category_id' => 0,
            'price' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name',
            'description',
            'add_info',
            'plati_category_id',
            'price',
        ]);

        $this->postJson("/api/goods/{$good->id}/digiseller/variable", [
            ...$this->fixedPayload(),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'unit_quantity',
            'unit_name',
        ]);

        Http::assertNothingSent();
    }

    public function test_a_good_cannot_be_published_twice(): void
    {
        $this->actingAsPublisher();
        $good = $this->createGood();
        $good->marketplaceMappings()->create([
            'marketplace' => 'plati',
            'marketplace_product_id' => 123456,
        ]);
        Http::fake();

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", $this->fixedPayload())
            ->assertConflict()
            ->assertJsonPath('message', 'This Good has already been published to DigiSeller.');

        Http::assertNothingSent();
    }

    public function test_authorized_user_can_publish_a_fixed_price_good_to_digiseller(): void
    {
        $this->actingAsPublisher();
        $good = $this->createGood();
        $good->details()->create([
            'good_key' => 'region',
            'good_name' => 'Region',
            'good_value' => 'Europe',
        ]);
        $this->createToken();
        Http::fake([
            'api.digiseller.com/api/product/create/uniquefixed*' => Http::response([
                'retval' => 0,
                'retdesc' => '',
                'errors' => null,
                'content' => ['product_id' => 123456],
            ]),
        ]);

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", $this->fixedPayload())
            ->assertOk()
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonPath('product_id', 123456)
            ->assertJsonPath('marketplace_mapping.marketplace', 'plati')
            ->assertJsonPath('marketplace_mapping.marketplace_product_id', 123456);

        $this->assertDatabaseHas('goods_marketplace_mappings', [
            'good_id' => $good->id,
            'marketplace' => 'plati',
            'marketplace_product_id' => 123456,
        ]);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/api/product/create/uniquefixed?token=test-token')
                && $request['content_type'] === 'text'
                && $request['name'] === [['locale' => 'en-US', 'value' => 'Telegram Stars']]
                && $request['description'] === [['locale' => 'en-US', 'value' => 'Delivered automatically.']]
                && $request['price'] === ['price' => 12.5, 'currency' => 'USD']
                && $request['categories'] === [[
                    'owner' => 1,
                    'category_id' => 4115,
                    'cataloguer_category_id' => 0,
                    'cataloguer_attributes' => [[
                        'attribute_id' => 0,
                        'attribute_value_id' => 0,
                    ]],
                ]]
                && $request['add_info'] === [['locale' => 'en-US', 'value' => 'Activation instructions.']]
                && $request['comission_partner'] === 1
                && $request['enabled'] === false;
        });
    }

    public function test_authorized_user_can_publish_a_variable_price_good_to_digiseller(): void
    {
        $this->actingAsPublisher();
        $good = $this->createGood();
        $this->createToken();
        Http::fake([
            'api.digiseller.com/api/product/create/uniqueunfixed*' => Http::response([
                'retval' => 0,
                'retdesc' => '',
                'errors' => null,
                'content' => ['product_id' => 654321],
            ]),
        ]);

        $this->postJson("/api/goods/{$good->id}/digiseller/variable", $this->variablePayload())
            ->assertOk()
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonPath('product_id', 654321)
            ->assertJsonPath('marketplace_mapping.marketplace', 'plati')
            ->assertJsonPath('marketplace_mapping.marketplace_product_id', 654321);

        $this->assertDatabaseHas('goods_marketplace_mappings', [
            'good_id' => $good->id,
            'marketplace' => 'plati',
            'marketplace_product_id' => 654321,
        ]);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/api/product/create/uniqueunfixed?token=test-token')
                && $request['content_type'] === 'digisellercode'
                && ! isset($request['price'])
                && $request['prices'] === [
                    'price' => 12.5,
                    'currency' => 'USD',
                    'unit_quantity' => 1000,
                    'unit_name' => 'Gold',
                ]
                && $request['categories'] === [[
                    'owner' => 1,
                    'category_id' => 4115,
                    'cataloguer_category_id' => 0,
                    'cataloguer_attributes' => [[
                        'attribute_id' => 0,
                        'attribute_value_id' => 0,
                    ]],
                ]]
                && $request['add_info'] === [['locale' => 'en-US', 'value' => 'Activation instructions.']]
                && $request['comission_partner'] === 1
                && $request['enabled'] === false;
        });
    }

    public function test_digiseller_failures_are_returned_without_persisting_a_product_id(): void
    {
        $this->actingAsPublisher();
        $good = $this->createGood();
        $this->createToken();
        Http::fake([
            'api.digiseller.com/api/product/create/uniquefixed*' => Http::response([
                'retval' => 1,
                'retdesc' => 'Validation error',
                'errors' => [[
                    'code' => 'category_invalid',
                    'message' => 'The selected category is invalid.',
                ]],
                'content' => null,
            ]),
        ]);

        $this->postJson("/api/goods/{$good->id}/digiseller/fixed", $this->fixedPayload())
            ->assertStatus(502)
            ->assertJsonPath('message', 'DigiSeller product creation failed.')
            ->assertJsonPath('digiseller.errors.0.code', 'category_invalid')
            ->assertJsonPath('digiseller.errors.0.message', 'The selected category is invalid.');

        $this->assertDatabaseMissing('goods_marketplace_mappings', [
            'good_id' => $good->id,
            'marketplace' => 'plati',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createGood(array $overrides = []): Good
    {
        return Good::create([
            'name' => 'Telegram Stars',
            'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
            ...$overrides,
        ]);
    }

    /** @return array<string, mixed> */
    private function fixedPayload(): array
    {
        return [
            'name' => 'Telegram Stars',
            'description' => 'Delivered automatically.',
            'add_info' => 'Activation instructions.',
            'price' => 12.5,
            'plati_category_id' => 4115,
        ];
    }

    /** @return array<string, mixed> */
    private function variablePayload(): array
    {
        return [
            ...$this->fixedPayload(),
            'unit_quantity' => 1000,
            'unit_name' => 'Gold',
        ];
    }

    private function actingAsPublisher(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('good_add_plati_detail', 'web'));
        Sanctum::actingAs($user);
    }

    private function createToken(): void
    {
        PlatiTokens::create([
            'token' => 'test-token',
            'expire_time' => now()->addHour(),
        ]);
    }
}
