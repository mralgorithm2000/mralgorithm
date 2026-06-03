<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BaleBotService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    private $token = '1819615058:8FKS2YsIolOp1NMyO5iiXynOaJwMUeQC93g';
    public $chat_ids = ['2138962610'];
    public function send($message){
        foreach ($this->chat_ids as $chatid) {
            $parameters = array(
                'chat_id' => $chatid,
                'text' => $message,
            );
            $this->sendMessages('sendMessage', $parameters);
        }
    }

    private function sendMessages($method, $data, $header = [])
    {
        $url = "https://tapi.bale.ai/bot" . $this->token . "/" . $method;


        try {
            $response = Http::withHeaders($header)
            ->asForm() // since CURLOPT_POSTFIELDS usually sends form data
            ->post($url, $data);

            $output = $response->body();

        } catch (\Exception $e) {
            Storage::put('curl_error.txt', $e->getMessage());
        }
    }

}
