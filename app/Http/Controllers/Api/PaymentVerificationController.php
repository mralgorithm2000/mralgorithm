<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SmService;
use App\Services\DigisellerService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
        ]);

        $digiseller = new DigisellerService;
        $uniqueCode = $validated['uniquecode'];
        $verification = $this->verificationData($digiseller->verifyPurchase($uniqueCode));
        $invoiceId = (string) ($verification['inv'] ?? '');

        if ($invoiceId === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.error'),
            ], 422);
        }

        $existing = Purchase::query()
            ->where('marketplace', 'plati')
            ->where('external_order_id', $invoiceId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'order_id' => $existing->external_order_id,
                'message' => __('payment.started_or_finished'),
            ]);
        }

        if (@$verification['unique_code_state']['date_delivery'] != null) {
            return response()->json([
                'success' => true,
                'order_id' => $verification['inv'],
                'message' => __('payment.started_or_finished'),
            ]);
        }

        $job = $this->doTheJob(
            $verification['id_goods'],
            $verification['cnt_goods'],
            $verification['options'],
            $verification['inv'],
            $verification,
        );

        $digiseller->markAsDelivered($uniqueCode);

        return response()->json([
            'success' => true,
            'order_id' => $verification['inv'],
            'message' => __('payment.success'),
        ]);
    }

    private function doTheJob($service_id, $quantity, $options, $invoice_id, array $verification)
    {
        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id', $service_id)->where('type', 'service_type')->value('option_id');
        $serviceLinkId = Option::where('plati_id', $service_id)->where('type', 'link')->value('option_id');

        $link = $optionsArr[$serviceLinkId];
        $plati_id = $optionsArr[$serviceTypeId];

        $service = SmService::where('plati_id', $plati_id)->firstOrFail();
        $serviceId = $service->api_id;

        $sold = max(0, (float) ($verification['amount_usd'] ?? 0));
        $profit = max(0, (float) ($verification['profit'] ?? $sold));

        try {
            [$purchase, $order] = DB::transaction(function () use ($service, $invoice_id, $sold, $profit, $link, $quantity, $plati_id, $serviceId) {
                $purchase = $service->purchases()->create([
                    'marketplace' => 'plati',
                    'external_order_id' => (string) $invoice_id,
                    'sold_price' => $sold,
                    'marketplace_fee' => max(0, $sold - $profit),
                    'cost_price' => 0,
                    'refunded_amount' => 0,
                    'status' => 'pending',
                ]);

                $order = Order::create([
                    'purchase_id' => $purchase->id,
                    'status' => 'init',
                    'link' => $link,
                    'quantity' => $quantity,
                    'api_id' => $plati_id,
                    'service_id' => $serviceId,
                    'user_code' => $invoice_id,
                ]);

                return [$purchase, $order];
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $purchase = Purchase::query()
                ->where('marketplace', 'plati')
                ->where('external_order_id', (string) $invoice_id)
                ->firstOrFail();
            $order = Order::where('purchase_id', $purchase->id)->firstOrFail();
        }

        Log::info('hi oreder', [
            'serviceLinkId' => $serviceLinkId,
            'order' => $order,
        ]);

        // return [
        //     'user_code' => rand(1000000, 9999999),
        // ];

        $response = Http::asForm()->post('https://panel.smmflw.com/api/iran', [
            'key' => env('FOLLOWERAN_API_KEY'),
            'action' => 'add',
            'service' => $serviceId,
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
                Order::where('id', $order->id)->update([
                    'order_id' => $result['order'],
                    'status' => $result['status'],
                ]);

                $actualCost = $result['charge'] ?? $result['cost'] ?? $result['price'] ?? null;
                if (is_numeric($actualCost) && (float) $actualCost > 0) {
                    $purchase->increment('cost_price', (float) $actualCost);
                }
            } else {
                Order::where('id', $order->id)->update([
                    'status' => 'failed',
                    'error' => $result['error'],
                ]);
            }

            return $result;
        } else {
            Order::where('id', $order->id)->update([
                'status' => 'failed',
                'error' => $response->body(),
            ]);
        }

        return [
            'user_code' => $order->user_code,
        ];
    }

    private function verificationData(array $verification): array
    {
        return isset($verification['response']) && is_array($verification['response'])
            ? array_replace($verification, $verification['response'])
            : $verification;
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
