<?php

namespace Tests\Feature\Api;

use App\Models\Parameter;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ParameterOptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_returns_pricing_and_supplier_fields(): void
    {
        $this->actingAsManager();
        $parameter = Parameter::create(['title' => 'Region', 'type' => 'dropdown']);
        $supplier = Supplier::create([
            'title' => 'Supplier',
            'website_url' => 'https://supplier.example',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/parameters/{$parameter->id}/options", [
            'option_name' => 'Europe',
            'option_value' => 'EU',
            'operator' => '+',
            'additional_price' => 2.5,
            'original_price' => 10.5,
            'selling_price' => 12.5,
            'supplier_id' => $supplier->id,
            'supplier_product_id' => 'SUP-EU-001',
        ])->assertCreated()
            ->assertJsonPath('data.original_price', '10.500000')
            ->assertJsonPath('data.selling_price', '12.500000')
            ->assertJsonPath('data.supplier_name', 'Supplier')
            ->assertJsonMissingPath('data.supplier_id')
            ->assertJsonPath('data.supplier_product_id', 'SUP-EU-001');

        $optionId = $response->json('data.id');

        $this->assertDatabaseHas('parameter_options', [
            'id' => $optionId,
            'supplier_id' => $supplier->id,
            'supplier_product_id' => 'SUP-EU-001',
        ]);

        $this->getJson("/api/parameters/{$parameter->id}/options")
            ->assertOk()
            ->assertJsonPath('data.0.original_price', '10.500000')
            ->assertJsonPath('data.0.selling_price', '12.500000')
            ->assertJsonPath('data.0.supplier_name', 'Supplier')
            ->assertJsonMissingPath('data.0.supplier_id')
            ->assertJsonPath('data.0.supplier_product_id', 'SUP-EU-001');
    }

    public function test_it_validates_pricing_and_supplier_fields(): void
    {
        $this->actingAsManager();
        $parameter = Parameter::create(['title' => 'Region', 'type' => 'dropdown']);

        $this->postJson("/api/parameters/{$parameter->id}/options", [
            'option_name' => 'Europe',
            'option_value' => 'EU',
            'original_price' => -1,
            'selling_price' => 'invalid',
            'supplier_id' => 999999,
            'supplier_product_id' => str_repeat('x', 256),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'original_price',
                'selling_price',
                'supplier_id',
                'supplier_product_id',
            ]);
    }

    private function actingAsManager(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('good_manage_parameters', 'web'));
        Sanctum::actingAs($user);
    }
}
