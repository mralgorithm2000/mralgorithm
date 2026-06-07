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

        //Instagram Likes | Instant Start | Extremely fast | 100–100,000 Likes
        "5927229"=> [
            "quantity" => "5175580",
            "link" => "5175871",
        ],

        //Telegram Members Boost – Budget Start Pack
        "5928220" => [
            "quantity"=> "5179930",
            "link"=> "5179958",
        ],

        //Telegram Members Boost – Standard Package
        "5930747" => [
            "quantity"=> "5196294",
            "link"=> "5196295",
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

        //Instagram Likes | Instant Start | Extremely fast | 100–100,000 Likes
        "5927229" => [
            "20930597" => 100,
            "20931380" => 500,
            "20931388" => 1000,
            "20931391" => 5000,
            "20931414" => 10000,
            "20931421" => 15000,
            "20931422" => 20000,
            "20931423" => 50000,
            "20931424" => 70000,
            "20931425" => 100000,
        ],

        // Telegram Members Boost – Budget Start Pack
        "5928220" => [
            "20960222" => 1000,
            "20960255" => 3000,
            "20960286" => 5000,
            "20960287" => 7000,
            "20960300" => 10000,
        ],

        //Telegram Members Boost – Standard Package
        "5930747" => [
            "21126582" => 500,
            "21126583" => 1000,
            "21126584" => 3000,
            "21126585" => 5000,
            "21126586" => 7000,
            "21126587" => 10000,
            "21126588" => 13000,
            "21126589" => 15000,
            "21126590" => 17000,
            "21126592" => 20000
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
