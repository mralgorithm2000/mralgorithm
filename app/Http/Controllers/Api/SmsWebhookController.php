<?php

namespace App\Http\Controllers\Api;

use App\Enums\NumberOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsWebhookRequest;
use App\Models\NumberOrder;
use App\Services\SmsCodeBroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsWebhookController extends Controller
{

    public function smscodex(Request $request, SmsCodeBroadcastService $smsCodeBroadcastService): JsonResponse
    {
        Log::info('request',[
            'req' => $request->all()
        ]);
        // $order = NumberOrder::where('phone_number',$request->post('phone'))
        // ->where('country_code',$request->post('country'))
        // ->firstOrFail();

        // $smsCode = $request->post('sms_code');

        // $order->update([
        //     'sms_code' => $smsCode,
        //     'status' => NumberOrderStatus::RECEIVED->value,
        // ]);

        // $smsCodeBroadcastService->broadcast($order->id, $smsCode);

        // return response()->json(['success' => true]);
    }
}
