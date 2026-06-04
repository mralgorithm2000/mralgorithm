<?php

namespace App\Http\Controllers\Followeran;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{

    private $optionMap = [
        //Instagram Likes for Posts | Instant Start | 100–20,000 Likes
        "5926634" => [
            "quantity" => "5172020",
            "link" => "5172037",
        ],

        //Instagram Likes | Instant Start | Fast | 100–100,000 Likes
        "5927202" => [
            "quantity" => "5175510",
            "link" => "5175524",
        ],
    ];

    private $quantityMap = [
        //Instagram Likes for Posts | Instant Start | 100–20,000 Likes
        "5926634" => [
            "20901519" => 100,
            "20901520" => 500,
            "20901552" => 1000,
            "20901564" => 5000,
            "20901566" => 10000,
            "20901570" => 15000,
            "20901658" => 20000,
        ],

        //Instagram Likes | Instant Start | Fast | 100–100,000 Likes
        "5927202" => [
            "20930022" => 100,
            "20930037" => 500,
            "20930047" => 1000,
            "20930048" => 5000,
            "20930051" => 10000,
            "20930052" => 15000,
            "20930063" => 20000,
            "20930064" => 50000,
            "20930066" => 70000,
            "20930068" => 100000,
        ],
    ];

    public function send(Request $request)
    {
        $id = $request->input('id');
        $options = $request->input('options');

        $optionsArr = [];

        foreach($options as $option){
            $optionsArr[$option['id']] = $option['user_data'];
        }

        $quantityID = $optionsArr[$this->optionMap[$id]['quantity']];
        $link = $optionsArr[$this->optionMap[$id]['link']];
        $quantity = $this->quantityMap[$id][$quantityID];

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
