<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Whatsapp extends BaseConfig
{
    /**
     * Diisi dari .env, contoh:
     *   whatsapp.gatewayUrl = https://owa.gusaha.id:5570/api/send-message
     *   whatsapp.apiKey     = 247bd699b4542b8ed04a5971601eb5d0ca78ac10
     */
    public string $gatewayUrl;
    public string $apiKey;

    public function __construct()
    {
        parent::__construct();

        $this->gatewayUrl = (string) (env('whatsapp.gatewayUrl') ?? 'https://owa.gusaha.id:5570/api/send-message');
        $this->apiKey     = (string) (env('whatsapp.apiKey') ?? '');
    }
}