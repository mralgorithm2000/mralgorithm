<?php

namespace Tests\Feature;

use App\Models\MarketplaceOptionMapping;
use App\Models\MarketplaceParameterMapping;
use App\Models\Parameter;
use App\Models\ParameterOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameter_relationships_and_marketplace_mappings_are_persisted(): void
    {
        $parameter = Parameter::create([
            'title' => 'Region',
            'type' => 'dropdown',
        ]);
        $option = $parameter->options()->create([
            'option_name' => 'Europe',
            'option_value' => 'EU',
            'operator' => '+',
            'additional_price' => 2.5,
        ]);
        $parameterMapping = $parameter->marketplaceMappings()->create([
            'marketplace' => 'Plati',
            'marketplace_parameter_id' => 602080,
        ]);
        $optionMapping = $option->marketplaceMappings()->create([
            'marketplace' => 'Plati',
            'marketplace_option_id' => 6020801,
        ]);

        $this->assertTrue($option->parameter->is($parameter));
        $this->assertTrue($parameterMapping->parameter->is($parameter));
        $this->assertTrue($optionMapping->parameterOption->is($option));
        $this->assertSame('2.500000', $option->additional_price);
    }

    public function test_deleting_a_parameter_cascades_to_options_and_mappings(): void
    {
        $parameter = Parameter::create([
            'title' => 'Region',
            'type' => 'dropdown',
        ]);
        $option = ParameterOption::create([
            'parameter_id' => $parameter->id,
            'option_name' => 'Europe',
            'option_value' => 'EU',
        ]);
        MarketplaceParameterMapping::create([
            'marketplace' => 'Plati',
            'marketplace_parameter_id' => 602080,
            'parameter_id' => $parameter->id,
        ]);
        MarketplaceOptionMapping::create([
            'marketplace' => 'Plati',
            'parameter_option_id' => $option->id,
            'marketplace_option_id' => 6020801,
        ]);

        $parameter->delete();

        $this->assertDatabaseCount('parameters', 0);
        $this->assertDatabaseCount('parameter_options', 0);
        $this->assertDatabaseCount('marketplace_parameter_mappings', 0);
        $this->assertDatabaseCount('marketplace_option_mappings', 0);
    }
}
