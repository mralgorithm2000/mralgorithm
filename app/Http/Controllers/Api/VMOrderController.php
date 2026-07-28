<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NumberOrder;
use App\Models\Option;
use App\Models\VirtualNumber;
use App\Services\DigisellerService;
use App\Services\NumberlandService;
use App\Services\SmsCodexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\CssSelector\XPath\Extension\FunctionExtension;

class VMOrderController extends Controller
{
    public function verify(Request $request)
    {
        $digiseller = new DigisellerService();
        $verification = $digiseller->verifyPurchase($request->post('uniquecode'));

        $order = NumberOrder::where('plati_order_id', $verification['inv'])->first();

        if($order){
            $service = $order->service;
            $serviceDetails = $this->getServiceDetails($service->type);
            return response()->json([
                'success' => true,
                'data' => [
                    'number' => $order->phone_number,
                    'country_code' => $order->country_code,
                    'expires_at' => $this->dateToMinutes($order->expires_at),
                    'serviceName' => $serviceDetails['name'],
                    'serviceIcon' => $serviceDetails['icon'],
                ],
                'message' => __('payment.success'),
            ]);
        }

        $job = $this->doTheJob($verification['id_goods'], $verification['options'], $verification['inv']);



        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => __('payment.success'),
        ]);
    }

    private function doTheJob($service_id, $options, $invoice_id)
    {
        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id', $service_id)->where('type', 'country')->value('option_id');

        $plati_id = $optionsArr[$serviceTypeId];

        $service = VirtualNumber::where('plati_id', $plati_id)->first();
        $serviceDetails = $this->getServiceDetails($service->type);

        $serviceClass = $this->getSourceService($service->source);
        $serviceInstance = new $serviceClass();
        $number = $serviceInstance->getNumber();

        $this->saveOrder($number['number'], $number['country_code'], 20, $service->id, $invoice_id);

        return [
            'number' => $number['number'],
            'country_code' => $number['country_code'],
            'expires_at' => $this->dateToMinutes(Carbon::now()->addMinutes(20)),
            'serviceName' => $serviceDetails['name'],
            'serviceIcon' => $serviceDetails['icon'],
        ];
    }

    private function getServiceDetails(?string $type): array
    {
        $serviceMap = [
            'telegram' => [
                'name' => 'Telegram',
                'icon' => asset('storage/icons/telegram.png'),
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => asset('storage/icons/instagram.png'),
            ],
        ];

        $serviceType = Str::lower(trim((string) $type));

        return $serviceMap[$serviceType] ?? [
            'name' => Str::headline($serviceType),
            'icon' => null,
        ];
    }

    private function getSourceService($serviceType){
        switch ($serviceType) {
            case 'smscodex':
                return SmsCodexService::class;
            case 'numberland':
                return NumberlandService::class;
        }
    }

    private function dateToMinutes($date){
        return Carbon::now()->diffInMinutes(Carbon::parse($date), false);
    }

    private function saveOrder($number, $country_code, $expires_at, $service_id, $invoice_id)
    {
        return NumberOrder::create([
            'virtual_number_id' => $service_id,
            'plati_order_id' => $invoice_id,
            'phone_number' => $number,
            'country_code' => $country_code,
            'expires_at' => Carbon::now()->addMinutes($expires_at),
        ]);
    }
}
