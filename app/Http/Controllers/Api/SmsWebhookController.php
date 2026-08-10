<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneAttempt;
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
            'request' => $request->all(),
        ]);

        $payload = $request->input('payload');

        if (
            ! $payload ||
            ($payload['stage'] ?? null) !== 'completed'
        ) {
            Log::info('sms codex status webhook', [
                'status' => 'Ignored',
                'stage' => $payload['stage'] ?? null,
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

        $attempt = PhoneAttempt::where('provider_order_id', $orderId)
            ->where('provider', 'smscodex')
            ->firstOrFail();

        $service->receiveSmsCode($attempt, (string) $smsCode);

        // Send to frontend via Pusher
        $smsCodeBroadcastService->broadcast($attempt->id, $smsCode);

        return response()->json([
            'success' => true,
        ]);
    }
}
