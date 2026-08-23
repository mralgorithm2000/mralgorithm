<?php

namespace Tests\Feature\Api;

use App\Models\Good;
use App\Models\Purchase;
use App\Models\RefundRequest;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RefundRequestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_show_require_authentication(): void
    {
        $this->getJson('/api/refund-requests')->assertUnauthorized();
        $this->getJson('/api/refund-requests/1')->assertUnauthorized();
    }

    public function test_list_requires_refund_list_permission(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/refund-requests')->assertForbidden();
    }

    public function test_show_requires_refund_view_permission(): void
    {
        $refundRequest = $this->createRefundRequest();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/refund-requests/{$refundRequest->id}")->assertForbidden();
    }

    public function test_status_update_requires_authentication(): void
    {
        $refundRequest = $this->createRefundRequest();

        $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [
            'status' => 'approved',
        ])->assertUnauthorized();
    }

    public function test_status_update_requires_refund_status_permission(): void
    {
        $refundRequest = $this->createRefundRequest();
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_status_update_validates_status_and_admin_note(): void
    {
        $this->actingAsUserWith('refund_status');
        $refundRequest = $this->createRefundRequest();

        $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [
            'status' => 'invalid',
            'admin_note' => ['not text'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'admin_note']);

        $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_authorized_user_can_update_refund_status_only(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('refund_status', 'web'));
        Sanctum::actingAs($admin);
        $originalAdmin = User::factory()->create();
        $refundRequest = $this->createRefundRequest('pending', 'Original reason', $originalAdmin);
        $originalPurchaseId = $refundRequest->purchase_id;
        $originalRequestedAt = $refundRequest->requested_at;
        $otherPurchase = Purchase::factory()->create(['goods_id' => $this->good()->id]);

        $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [
            'status' => 'completed',
            'admin_note' => 'Refund completed by support.',
            'reason' => 'Attempted replacement reason',
            'purchase_id' => $otherPurchase->id,
            'admin_id' => $originalAdmin->id,
            'requested_at' => now()->subYear()->toISOString(),
        ])->assertOk()
            ->assertJsonPath('data.id', $refundRequest->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.admin_note', 'Refund completed by support.')
            ->assertJsonPath('data.reason', 'Original reason')
            ->assertJsonPath('data.purchase.id', $originalPurchaseId);

        $refundRequest->refresh();

        $this->assertSame($admin->id, $refundRequest->admin_id);
        $this->assertSame($originalPurchaseId, $refundRequest->purchase_id);
        $this->assertSame('Original reason', $refundRequest->reason);
        $this->assertTrue($originalRequestedAt->equalTo($refundRequest->requested_at));
        $this->assertNotNull($refundRequest->reviewed_at);
        $this->assertNotNull($refundRequest->completed_at);
        $this->assertSame('refunded', $refundRequest->purchase->fresh()->status);
    }

    public function test_refund_status_updates_the_purchase_status_when_required(): void
    {
        $this->actingAsUserWith('refund_status');

        $rejected = $this->createRefundRequest();
        $rejected->purchase->update(['status' => 'pending']);
        $this->putJson("/api/refund-requests/{$rejected->id}/status", [
            'status' => 'rejected',
        ])->assertOk();
        $this->assertSame('completed', $rejected->purchase->fresh()->status);

        foreach (['pending', 'approved'] as $status) {
            $refundRequest = $this->createRefundRequest();
            $refundRequest->purchase->update(['status' => 'refund_pending']);

            $this->putJson("/api/refund-requests/{$refundRequest->id}/status", [
                'status' => $status,
            ])->assertOk();

            $this->assertSame('refund_pending', $refundRequest->purchase->fresh()->status);
        }
    }

    public function test_authorized_user_receives_paginated_refund_requests(): void
    {
        $this->actingAsUserWith('refund_list');
        $this->createRefundRequest('pending', 'First reason');
        $this->createRefundRequest('approved', 'Second reason');

        $this->getJson('/api/refund-requests?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'approved')
            ->assertJsonPath('data.0.reason', 'Second reason')
            ->assertJsonPath('data.0.purchase.marketplace', 'Plati')
            ->assertJsonPath('data.0.purchase.good.name', 'Telegram Stars')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'status', 'reason', 'requested_at', 'reviewed_at', 'completed_at',
                    'admin_note', 'created_at', 'updated_at', 'purchase' => [
                        'id', 'marketplace_order_id', 'marketplace', 'good' => ['name'],
                    ],
                ]],
                'links', 'meta',
            ]);
    }

    public function test_authorized_user_can_filter_refund_requests(): void
    {
        $this->actingAsUserWith('refund_list');
        $pending = $this->createRefundRequest('pending', 'Pending request');
        $approved = $this->createRefundRequest('approved', 'Approved request');
        $rejected = $this->createRefundRequest('rejected', 'Rejected request');

        $pending->purchase->update(['marketplace_order_id' => 'REFUND-ORDER-001']);
        $approved->purchase->update(['marketplace_order_id' => 'REFUND-ORDER-002']);
        $rejected->purchase->update(['marketplace_order_id' => 'REFUND-ORDER-003']);

        $this->getJson('/api/refund-requests?status=approved')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $approved->id);

        $this->getJson("/api/refund-requests?purchase_id={$pending->purchase_id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $pending->id);

        $this->getJson('/api/refund-requests?marketplace_order_id=REFUND-ORDER-003')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $rejected->id);
    }

    public function test_authorized_user_can_view_full_refund_request_details(): void
    {
        $this->actingAsUserWith('refund_view');
        $admin = User::factory()->create(['name' => 'Refund Admin']);
        $refundRequest = $this->createRefundRequest('approved', 'Customer request', $admin);
        $refundRequest->purchase->good->details()->create([
            'good_key' => 'region',
            'good_name' => 'Region',
            'good_value' => 'Europe',
        ]);

        $this->getJson("/api/refund-requests/{$refundRequest->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $refundRequest->id)
            ->assertJsonPath('data.purchase.marketplace_order_id', $refundRequest->purchase->marketplace_order_id)
            ->assertJsonPath('data.purchase.good.name', 'Telegram Stars')
            ->assertJsonPath('data.purchase.good.details.0.good_key', 'region')
            ->assertJsonPath('data.purchase.good.type.title', 'Digital')
            ->assertJsonPath('data.admin.id', $admin->id)
            ->assertJsonPath('data.admin.name', 'Refund Admin');
    }

    private function actingAsUserWith(string $permission): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        Sanctum::actingAs($user);
    }

    private function createRefundRequest(
        string $status = 'pending',
        string $reason = 'Service was not delivered',
        ?User $admin = null,
    ): RefundRequest {
        $purchase = Purchase::factory()->create(['goods_id' => $this->good()->id]);

        return RefundRequest::create([
            'purchase_id' => $purchase->id,
            'status' => $status,
            'reason' => $reason,
            'requested_at' => now(),
            'reviewed_at' => $admin === null ? null : now(),
            'admin_id' => $admin?->id,
            'admin_note' => $admin === null ? null : 'Approved by support.',
        ]);
    }

    private function good(): Good
    {
        return Good::firstOrCreate(
            ['name' => 'Telegram Stars'],
            [
                'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
            ],
        );
    }
}
