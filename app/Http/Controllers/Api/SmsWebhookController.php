<?php

namespace App\Http\Controllers\Api;

use App\Enums\NumberOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\NumberOrder;
use App\Services\NumberlandService;
use App\Services\SmsCodeBroadcastService;
use App\Services\SmsCodexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsWebhookController extends Controller
{
    public function smscodex(
        Request $request,
        SmsCodeBroadcastService $smsCodeBroadcastService
    ) {
        Log::info('SMSCodex webhook', [
            'req' => $request->all(),
        ]);

        $payload = $request->input('req.payload');

        // Only process when the SMS has been received
        if (
            ! $payload ||
            ($payload['stage'] ?? null) !== 'completed'
        ) {
            Log::info('sms codex status webhook', [
                'status' => 'Ignored',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ignored',
            ]);
        }

        $orderId = $payload['order_id'] ?? null;

        if (! $orderId) {
            Log::info('sms codex status webhook', [
                'status' => 'Missing order_id',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Missing order_id',
            ], 400);
        }

        $service = new SmsCodexService;

        Log::info('sms codex status webhook', [
            'status' => 'getting status',
        ]);
        // Pass the order id to your API
        $status = $service->getOrderStatus($orderId);

        $smsCode = $status['last_code']
            ?? data_get($status, 'sms.0.code');

        if (! $smsCode) {

            Log::info('sms codex status webhook', [
                'status' => 'no sms code',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'No SMS code yet',
            ]);
        }

        $order = NumberOrder::where('source_order_id', $orderId)
            ->firstOrFail();

        $order->update([
            'sms_code' => $smsCode,
            'status' => NumberOrderStatus::RECEIVED->value,
        ]);

        // Send to frontend via Pusher
        $smsCodeBroadcastService->broadcast($order->id, $smsCode);

        return response()->json([
            'success' => true,
        ]);
    }

    public function numberland(Request $request)
    {
        $order_id = $request->input('order_id');

        $numberlandService = new NumberlandService;
        $status = $numberlandService->getOrderStatus($order_id);
        $smsCode = $status['last_code']
            ?? data_get($status, 'sms.0.code');

        if (! $smsCode) {
            return;
        }

        $updated = NumberOrder::where('source_order_id', $order_id)->update([
            'sms_code' => (string) $smsCode,
            'status' => NumberOrderStatus::RECEIVED->value,
        ]);

        if (! $updated) {
            return;
        }
        $smsCodeBroadcastService = new SmsCodeBroadcastService;
        $smsCodeBroadcastService->broadcast($order_id, (string) $smsCode);
    }
}
