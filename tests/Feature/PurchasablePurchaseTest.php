<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\SmService;
use App\Models\VirtualNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasablePurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_virtual_number_and_smm_purchases_use_stable_morph_aliases(): void
    {
        $virtualNumber = VirtualNumber::create([
            'country' => 'US', 'original_price' => '0.2', 'source' => 'smscodex',
            'type' => 'telegram', 'plati_id' => 'vn-1',
        ]);
        $smService = SmService::create([
            'plati_id' => '11111111-1111-1111-1111-111111111111', 'api_id' => '12',
            'type' => 'instagram_like', 'origin' => 'followeran', 'name' => 'Likes',
            'sm' => 'instagram', 'min' => '10', 'max' => '1000',
        ]);

        $virtualPurchase = $virtualNumber->purchases()->create($this->attributes('code-vn', 'inv-vn'));
        $smPurchase = $smService->purchases()->create($this->attributes('code-sm', 'inv-sm'));

        $this->assertSame('virtual_number', $virtualPurchase->purchasable_type);
        $this->assertSame('sm_service', $smPurchase->purchasable_type);
        $this->assertTrue($virtualPurchase->purchasable->is($virtualNumber));
        $this->assertTrue($smPurchase->purchasable->is($smService));
        $this->assertSame('0.330000', $virtualPurchase->sold_price);
        $this->assertSame('0.020000', $virtualPurchase->marketplace_fee);
    }

    public function test_unique_code_and_marketplace_invoice_are_independently_unique(): void
    {
        $service = VirtualNumber::create([
            'country' => 'US', 'original_price' => '0.2', 'source' => 'smscodex',
            'type' => 'telegram', 'plati_id' => 'vn-unique',
        ]);
        $service->purchases()->create($this->attributes('same-code', 'same-invoice'));

        foreach ([
            $this->attributes('same-code', 'other-invoice'),
            $this->attributes('other-code', 'same-invoice'),
        ] as $attributes) {
            try {
                $service->purchases()->create($attributes);
                $this->fail('Expected a purchase uniqueness violation.');
            } catch (UniqueConstraintViolationException) {
                $this->assertDatabaseCount('purchases', 1);
            }
        }
    }

    private function attributes(string $code, string $invoice): array
    {
        return [
            'marketplace' => 'plati', 'external_order_id' => $invoice, 'unique_code' => $code,
            'sold_price' => 0.33, 'cost_price' => 0, 'marketplace_fee' => 0.02,
            'refunded_amount' => 0, 'status' => 'pending',
        ];
    }
}
