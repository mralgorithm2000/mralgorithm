<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DigisellerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentVerificationController extends Controller
{
    public function verify(Request $request){
        $digiseller = new DigisellerService();
        $verification = $digiseller->verifyPurchase($request->post('uniquecode'));

        Log::info('verification',[
            'verification' => $verification
        ]);
        return response()->json([
            'success' => true,
            'order_id' => "123456798",
            'message' => 'Payment verified successfully.',
        ]);
    }
}
