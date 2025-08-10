<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClickpayService
{
    protected string $baseUrl;
    protected string $serverKey;
    protected int $profileId;

    public function __construct()
    {
        $this->baseUrl   = config('services.clickpay.base_url');
        $this->serverKey = config('services.clickpay.server_key');
        $this->profileId = (int) config('services.clickpay.profile_id');
    }

    protected function client()
    {
        return Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    public function paymentRequest(array $payload)
    {
        $payload['profile_id'] = $payload['profile_id'] ?? $this->profileId;
        return $this->client()->post('/payment/request', $payload);
    }
}
