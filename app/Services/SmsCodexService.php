<?php

namespace App\Services;


class SmsCodexService
{
    public function getNumber(){
        return [
            'number' => '123456789',
            'country_code' => '+1',
        ];
    }
}
