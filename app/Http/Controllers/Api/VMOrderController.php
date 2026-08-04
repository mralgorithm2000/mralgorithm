<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhoneAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use App\Services\DigisellerService;
use App\Services\NumberlandService;
use App\Services\SmsCodexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VMOrderController extends Controller
{
    public function verify(Request $request)
    {
        $digiseller = new DigisellerService;
        $verification = $digiseller->verifyPurchase($request->uniquecode);

        if (@$verification['inv'] == '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.error'),
            ]);
        }

        $purchase = Purchase::where('unique_code', $request->uniquecode)->first()
            ?? Purchase::where('plati_order_id', $verification['inv'])->first();

        if ($purchase && str_starts_with($purchase->unique_code, 'legacy-')) {
            $purchase->update(['unique_code' => $request->uniquecode]);
        }

        if ($purchase && ($attempt = $purchase->activeAttempt())) {
            $this->authorizeAttemptChannel($request, $attempt);

            $service = $purchase->virtualNumber;
            $serviceDetails = $this->getServiceDetails($service->type);
            $statusDetails = $this->getStatusDetails($attempt->status);

            return response()->json([
                'success' => true,
                'data' => [
                    'number' => $attempt->phone_number,
                    'country_code' => $attempt->country_code,
                    'expires_at' => $this->dateToMinutes($attempt->expires_at),
                    'serviceName' => $serviceDetails['name'],
                    'serviceIcon' => $serviceDetails['icon'],
                    'status' => $statusDetails['value'],
                    'statusLabel' => $statusDetails['label'],
                    'order_id' => $attempt->id,
                    'sms_code' => $attempt->sms_code,
                ],
                'message' => __('payment.success'),
            ]);
        }

        try {
            $job = $this->doTheJob(
                $verification['id_goods'],
                $verification['options'],
                $verification['inv'],
                $request->uniquecode,
                $purchase,
            );
        } catch (\Exception $e) {
            Log::info('puchace ctach status', [
                'status' => 'error',
                'body' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('sms.unable_to_purchase'),
                'type' => 'purchase_error',
            ]);
        }
        // $job = $this->doTheJob($verification['id_goods'], $verification['options'], $verification['inv']);

        $this->authorizeAttemptChannel($request, $job['attempt']);

        return response()->json([
            'success' => true,
            'data' => $job['data'],
            'message' => __('payment.success'),
        ]);
    }

    private function doTheJob($service_id, $options, $invoice_id, string $uniqueCode, ?Purchase $purchase)
    {
        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id', $service_id)->where('type', 'country')->value('option_id');

        $plati_id = $optionsArr[$serviceTypeId];

        $service = VirtualNumber::where('plati_id', $plati_id)->first();
        $serviceDetails = $this->getServiceDetails($service->type);

        $purchase ??= Purchase::firstOrCreate(
            ['unique_code' => $uniqueCode],
            [
                'plati_order_id' => $invoice_id,
                'virtual_number_id' => $service->id,
            ],
        );

        $serviceClass = $this->getSourceService($service->source);
        $serviceInstance = new $serviceClass;
        $attempt = $serviceInstance->getNumber($service, $purchase);

        $statusDetails = $this->getStatusDetails($attempt->status);

        return [
            'attempt' => $attempt,
            'data' => [
                'number' => $attempt->phone_number,
                'country_code' => $attempt->country_code,
                'expires_at' => $this->dateToMinutes($attempt->expires_at),
                'serviceName' => $serviceDetails['name'],
                'serviceIcon' => $serviceDetails['icon'],
                'status' => $statusDetails['value'],
                'statusLabel' => $statusDetails['label'],
                'order_id' => $attempt->id,
                'sms_code' => '',
            ],
        ];
    }

    private function authorizeAttemptChannel(Request $request, PhoneAttempt $attempt): void
    {
        $request->session()->put("phone_attempt_ids.{$attempt->id}", true);
    }

    private function getStatusDetails(?string $status): array
    {
        $orderStatus = PhoneAttemptStatus::tryFrom((string) $status)
            ?? PhoneAttemptStatus::WAITING;

        return [
            'value' => $orderStatus->value,
            'label' => $orderStatus->label(),
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

    private function getSourceService($serviceType)
    {
        switch ($serviceType) {
            case 'smscodex':
                return SmsCodexService::class;
            case 'numberland':
                return NumberlandService::class;
        }
    }

    private function dateToMinutes($date)
    {
        $now = Carbon::now();
        $expires = Carbon::parse($date);

        return $now->diffInSeconds($expires);
    }
}
