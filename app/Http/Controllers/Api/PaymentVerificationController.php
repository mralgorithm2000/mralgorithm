<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Order;
use App\Models\Service;
use App\Models\SmService;
use App\Services\DigisellerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentVerificationController extends Controller
{
    public function verify(Request $request){
        $digiseller = new DigisellerService();
        $verification = $digiseller->verifyPurchase($request->post('uniquecode'));

        $job = $this->doTheJob($verification['id_goods'], $verification['cnt_goods'],$verification['options']);

        $verification = $digiseller->markAsDelivered($request->post('uniquecode'));
        return response()->json([
            'success' => true,
            'order_id' => $job['user_code'],
            'message' => __('payment.success'),
        ]);
    }

    private function doTheJob($service_id,$quantity, $options){
        $optionsArr = [];

        foreach($options as $option){
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id',$service_id)->where('type','service_type')->value('option_id');
        $serviceLinkId = Option::where('plati_id',$service_id)->where('type','link')->value('option_id');

        $link = $optionsArr[$serviceLinkId];
        $plati_id = $optionsArr[$serviceTypeId];

        $serviceId = SmService::where('plati_id',$plati_id)->value('api_id');


        $order = Order::create([
            'status' => 'init',
            'link' => $link,
            'quantity' => $quantity,
            'api_id' => $plati_id,
            'service_id' => $serviceId,
            'user_code' => $this->makeUniqueRandId()
        ]);

        Log::info('hi oreder',[
            'serviceLinkId' => $serviceLinkId,
            'order' => $order
        ]);

        return [
            'user_code' => $order->user_code,
        ];
        $response = Http::asForm()->post('https://panel.smmflw.com/api/iran', [
            'key' => env('FOLLOWERAN_API_KEY'),
            'action' => 'add',
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'is_test' => 0,
        ]);

        if ($response->successful()) {
            $result = $response->json();


            if(isset($result['order'])){
                Order::where('id', $order->id)->update([
                    'order_id' => $result['order'],
                    'status' => $result['status']
                ]);
            }else{
                Order::where('id', $order->id)->update([
                    'status' => 'failed',
                    'error' => $result['error']
                ]);
            }
            return $result;
        } else {
            Order::where('id', $order->id)->update([
                'status' => 'failed',
                'error' => $response->body()
            ]);
        }
        return [
            'user_code' => $order->user_code,
        ];
    }

    private function makeUniqueRandId(){
        $randid = rand(1000000,9999999);

        $order = Order::where('user_code',$randid)->first();

        if($order){
            return $this->makeUniqueRandId();
        }
        return $randid;
    }
}
