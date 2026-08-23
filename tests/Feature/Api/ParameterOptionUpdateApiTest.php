<?php

namespace Tests\Feature\Api;

use App\Models\Parameter;
use App\Models\ParameterOption;
use App\Models\PlatiTokens;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ParameterOptionUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_plati_before_updating_the_local_option(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        $this->createToken();

        Http::fake([
            'api.digiseller.com/api/products/options/7001/variants/8001*' => Http::response([
                'retval' => 0,
                'retdesc' => null,
                'errors' => null,
                'content' => ['status' => 'Success'],
            ]),
        ]);

        $this->putJson("/api/parameters/{$parameter->id}/options/{$option->id}", $this->payload())
            ->assertOk()
            ->assertJsonPath('data.option_name', 'Updated Europe')
            ->assertJsonPath('marketplace_mappings.0.marketplace_option_id', 8001);

        $this->assertDatabaseHas('parameter_options', [
            'id' => $option->id,
            'option_name' => 'Updated Europe',
            'option_value' => 'EU2',
            'operator' => '-',
            'additional_price' => 4.25,
        ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/api/products/options/7001/variants/8001?token=test-token')
            && $request['name'] === [
                ['locale' => 'en-US', 'value' => 'Updated Europe'],
                ['locale' => 'ru-RU', 'value' => 'Updated Europe'],
            ]
            && $request['type'] === 'priceminus'
            && $request['rate'] === 4.25);
    }

    public function test_it_keeps_the_local_option_unchanged_when_plati_fails(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        $this->createToken();

        Http::fake([
            'api.digiseller.com/*' => Http::response([
                'retval' => 1,
                'retdesc' => 'Invalid variant.',
                'errors' => [['message' => 'Invalid variant.']],
            ]),
        ]);

        $this->putJson("/api/parameters/{$parameter->id}/options/{$option->id}", $this->payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Plati parameter publishing failed.');

        $this->assertDatabaseHas('parameter_options', [
            'id' => $option->id,
            'option_name' => 'Europe',
            'option_value' => 'EU',
            'operator' => '+',
            'additional_price' => 2.5,
        ]);
    }

    public function test_it_rejects_an_option_from_another_parameter_without_calling_plati(): void
    {
        $this->actingAsManager();
        [$parameter] = $this->mappedOption();
        $otherParameter = Parameter::create(['title' => 'Other', 'type' => 'dropdown']);
        $otherOption = $otherParameter->options()->create([
            'option_name' => 'Other',
            'option_value' => 'other',
        ]);
        Http::fake();

        $this->putJson("/api/parameters/{$parameter->id}/options/{$otherOption->id}", $this->payload())
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_an_empty_marketplace_list_updates_only_the_local_option(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        Http::fake();

        $payload = $this->payload();
        $payload['marketplaces'] = [];

        $this->putJson("/api/parameters/{$parameter->id}/options/{$option->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.option_name', 'Updated Europe')
            ->assertJsonCount(0, 'marketplace_mappings');

        Http::assertNothingSent();
    }

    public function test_an_unsupported_marketplace_returns_a_clear_error_before_updates(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        Http::fake();

        $payload = $this->payload();
        $payload['marketplaces'] = ['ggsel'];

        $this->putJson("/api/parameters/{$parameter->id}/options/{$option->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonPath('unsupported_marketplaces.0', 'ggsel');

        $this->assertDatabaseHas('parameter_options', [
            'id' => $option->id,
            'option_name' => 'Europe',
        ]);
        Http::assertNothingSent();
    }

    public function test_it_deletes_from_plati_before_soft_deleting_the_local_option(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        $this->createToken();
        Http::fake([
            'api.digiseller.com/api/products/options/7001/variants/8001/delete*' => Http::response([
                'retval' => 0,
                'retdesc' => null,
                'errors' => null,
                'content' => ['status' => 'Success'],
            ]),
        ]);

        $this->deleteJson("/api/parameters/{$parameter->id}/options/{$option->id}", [
            'marketplaces' => ['plati'],
        ])->assertOk()
            ->assertJsonPath('option_id', $option->id)
            ->assertJsonPath('deleted_from_marketplaces.0', 'plati');

        $this->assertSoftDeleted('parameter_options', ['id' => $option->id]);
        $this->assertDatabaseHas('marketplace_option_mappings', [
            'parameter_option_id' => $option->id,
            'marketplace_option_id' => 8001,
        ]);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/api/products/options/7001/variants/8001/delete?token=test-token'));
    }

    public function test_an_empty_marketplace_list_only_soft_deletes_the_local_option(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        Http::fake();

        $this->deleteJson("/api/parameters/{$parameter->id}/options/{$option->id}", [
            'marketplaces' => [],
        ])->assertOk()
            ->assertJsonCount(0, 'deleted_from_marketplaces');

        $this->assertSoftDeleted('parameter_options', ['id' => $option->id]);
        Http::assertNothingSent();
    }

    public function test_a_marketplace_delete_failure_keeps_the_local_option_active(): void
    {
        $this->actingAsManager();
        [$parameter, $option] = $this->mappedOption();
        $this->createToken();
        Http::fake([
            'api.digiseller.com/*' => Http::response([
                'retval' => 1,
                'retdesc' => 'Unable to delete variant.',
                'errors' => [['message' => 'Unable to delete variant.']],
            ]),
        ]);

        $this->deleteJson("/api/parameters/{$parameter->id}/options/{$option->id}", [
            'marketplaces' => ['plati'],
        ])->assertUnprocessable()
            ->assertJsonPath('marketplace', 'plati');

        $this->assertDatabaseHas('parameter_options', [
            'id' => $option->id,
            'deleted_at' => null,
        ]);
    }

    /** @return array{Parameter, ParameterOption} */
    private function mappedOption(): array
    {
        $parameter = Parameter::create(['title' => 'Region', 'type' => 'dropdown']);
        $option = $parameter->options()->create([
            'option_name' => 'Europe',
            'option_value' => 'EU',
            'operator' => '+',
            'additional_price' => 2.5,
        ]);
        $parameter->marketplaceMappings()->create([
            'marketplace' => 'plati',
            'marketplace_parameter_id' => 7001,
        ]);
        $option->marketplaceMappings()->create([
            'marketplace' => 'plati',
            'marketplace_option_id' => 8001,
        ]);

        return [$parameter, $option];
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'option_name' => 'Updated Europe',
            'option_value' => 'EU2',
            'operator' => '-',
            'additional_price' => 4.25,
            'marketplaces' => ['plati'],
        ];
    }

    private function actingAsManager(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('good_manage_plati_parameters', 'web'));
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
