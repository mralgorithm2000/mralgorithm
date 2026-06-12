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

        $this->doTheJob($verification['id_goods'], $verification['cnt_goods'],$verification['options']);
        return response()->json([
            'success' => true,
            'order_id' => "123456798",
            'message' => 'Payment verified successfully.',
        ]);
    }

    private function doTheJob($service_id,$quantity, $options){
        $optionsArr = [];

        foreach($options as $option){
            $optionsArr[$option['id']] = $option['variant_id'];
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
            'service_id' => $serviceId
        ]);

        Log::info('hi oreder',[
            'order' => $order
        ]);


        return 0;
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
    }
}
