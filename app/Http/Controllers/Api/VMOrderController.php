<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\VirtualNumber;
use App\Services\DigisellerService;
use App\Services\NumberlandService;
use App\Services\SmsCodexService;
use Illuminate\Http\Request;
use Symfony\Component\CssSelector\XPath\Extension\FunctionExtension;

class VMOrderController extends Controller
{
    public function verify(Request $request)
    {
        $digiseller = new DigisellerService();
        $verification = $digiseller->verifyPurchase($request->post('uniquecode'));

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

        $serviceClass = $this->getSourceService($service->source);
        $serviceInstance = new $serviceClass();
        $number = $serviceInstance->getNumber();

        return [
            'number' => $number['number'],
            'country_code' => $number['country_code'],
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
}
