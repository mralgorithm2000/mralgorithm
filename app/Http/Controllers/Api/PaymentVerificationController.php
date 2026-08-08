<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SmService;
use App\Services\DigisellerService;
use App\Enums\PurchaseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $digiseller = new DigisellerService;
        $verification = $digiseller->verifyPurchase($request->post('uniquecode'));
        $response = $this->normalizeDigisellerResponse($verification);

        $invoiceId = (string) ($response['inv'] ?? $verification['inv'] ?? '');

        if ($invoiceId === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.error'),
            ], 422);
        }

        $uniqueCode = $request->post('uniquecode');
        $purchase = $this->createOrUpdateSmPurchase($uniqueCode, $response, $invoiceId);

        if (data_get($verification, 'unique_code_state.date_delivery') !== null) {
            return response()->json([
                'success' => true,
                'order_id' => $invoiceId,
                'message' => __('payment.started_or_finished'),
            ]);
        }

        DB::transaction(function () use ($response, $uniqueCode, $invoiceId) {
            $job = $this->doTheJob($response['id_goods'] ?? $response['id_good'] ?? null, $response['cnt_goods'] ?? 1, $response['options'] ?? [], $invoiceId);

            Log::info('SMM order job completed', [
                'purchase_id' => $job['purchase']->id ?? null,
                'attempt' => $job['attempt'] ?? null,
            ]);
        });

        $digiseller->markAsDelivered($uniqueCode);

        return response()->json([
            'success' => true,
            'order_id' => $invoiceId,
            'message' => __('payment.success'),
        ]);
    }

    private function normalizeDigisellerResponse(array $payload): array
    {
        return $payload['response'] ?? $payload;
    }

    private function createOrUpdateSmPurchase(string $uniqueCode, array $response, string $invoiceId): Purchase
    {
        $amountUsd = $this->normalizeDecimal($response['amount_usd'] ?? $response['amount'] ?? 0);
        $profit = $this->normalizeDecimal($response['profit'] ?? 0);
        $marketplaceFee = max($amountUsd - $profit, 0);

        $serviceTypeId = Option::where('plati_id', $response['id_goods'] ?? null)
            ->where('type', 'service_type')
            ->value('option_id');

        $platiId = $response['options'][$serviceTypeId] ?? null;
        $service = SmService::where('plati_id', $platiId)->first();

        $purchase = Purchase::where('unique_code', $uniqueCode)
            ->orWhere(function ($query) use ($invoiceId) {
                $query->where('marketplace', 'plati')
                    ->where('external_order_id', $invoiceId);
            })
            ->first();

        if ($purchase) {
            $purchase->forceFill([
                'unique_code' => $uniqueCode,
                'sold_price' => $amountUsd,
                'marketplace_fee' => $marketplaceFee,
                'status' => PurchaseStatus::PENDING->value,
            ])->save();

            return $purchase;
        }

        if (! $service) {
            throw new \Exception('Unable to resolve SMM service for purchase.');
        }

        return $service->purchases()->create([
            'unique_code' => $uniqueCode,
            'marketplace' => 'plati',
            'external_order_id' => $invoiceId,
            'sold_price' => $amountUsd,
            'cost_price' => 0,
            'marketplace_fee' => $marketplaceFee,
            'refunded_amount' => 0,
            'status' => PurchaseStatus::PENDING->value,
        ]);
    }

    private function normalizeDecimal(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function doTheJob($service_id, $quantity, $options, $invoice_id)
    {
        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id', $service_id)->where('type', 'service_type')->value('option_id');
        $serviceLinkId = Option::where('plati_id', $service_id)->where('type', 'link')->value('option_id');

        $link = $optionsArr[$serviceLinkId];
        $plati_id = $optionsArr[$serviceTypeId];

        $service = SmService::where('plati_id', $plati_id)->first();

        $order = Order::create([
            'status' => 'init',
            'link' => $link,
            'quantity' => $quantity,
            'api_id' => $plati_id,
            'service_id' => $service->api_id,
            'user_code' => $invoice_id,
        ]);

        Log::info('hi oreder', [
            'serviceLinkId' => $serviceLinkId,
            'order' => $order,
        ]);

        $response = Http::asForm()->post('https://panel.smmflw.com/api/iran', [
            'key' => env('FOLLOWERAN_API_KEY'),
            'action' => 'add',
            'service' => $service->api_id,
            'link' => $link,
            'quantity' => $quantity,
            'is_test' => 0,
        ]);

        Log::info('api response', [
            'response' => $response->json(),
        ]);

        if ($response->successful()) {
            $result = $response->json();

            if (isset($result['order'])) {
                $order->update([
                    'order_id' => $result['order'],
                    'status' => $result['status'],
                ]);
            } else {
                $order->update([
                    'status' => 'failed',
                    'error' => $result['error'] ?? null,
                ]);
            }

            return $result;
        }

        $order->update([
            'status' => 'failed',
            'error' => $response->body(),
        ]);

        return [
            'user_code' => $order->user_code,
        ];
    }

    private function makeUniqueRandId()
    {
        $randid = rand(1000000, 9999999);

        $order = Order::where('user_code', $randid)->first();

        if ($order) {
            return $this->makeUniqueRandId();
        }

        return $randid;
    }
}
