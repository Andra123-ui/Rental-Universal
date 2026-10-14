<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Whatsapp extends BaseConfig
{
    public string $gatewayUrl;
    public string $apiKey;
    public int $deviceId;

    public function __construct()
    {
        parent::__construct();

        // Prioritas baca dari .env, fallback ke nilai default
        $this->gatewayUrl = env('whatsapp.gatewayUrl', '');
        $this->apiKey     = env('whatsapp.apiKey', '');
        $this->deviceId   = (int) env('whatsapp.deviceId', 0);
    }
}