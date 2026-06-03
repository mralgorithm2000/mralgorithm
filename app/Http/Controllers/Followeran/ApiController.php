<?php

namespace App\Http\Controllers\Followeran;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    public function send(Request $request)
    {
        $id = $request->post('id');
        $link = $request->post('link');
        $quantity = $request->post('quantity');
        $api_key = $request->post('api_key');

        $mykey = "Bnm34$4@dDza";

        // if($api_key != $mykey){
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Invalid API Key'
        //     ], 401);
        // }

        $serviceId = SmService::where('random_id', $id)->value('api_id');

        $order = Order::create([
            'status' => 'init',
            'link' => $link,
            'quantity' => $quantity,
            'api_id' => $id,
            'service_id' => $serviceId
        ]);

        $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
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
