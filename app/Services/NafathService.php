<?php

namespace App\Services;

use App\Models\NafathVerification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class NafathService
{
    protected Client $client;
    protected array  $headers;

    const AUTHORIZE_ENDPOINT = '/nafath/api/v1/client/authorize/';
    const STATUS_ENDPOINT = 'https://api.arabianpay.co/api/v1/check-nafath-status';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 20,
            'verify'  => false,
        ]);

        $this->headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'apiKey ' . env('NAFATH_API_KEY'),
        ];
    }

    /**
     * Initiate a Nafath session.
     */
    public function initiateVerification(string $nationalId, string $service = 'OpenAccount'): ?array
    {
        try {
            $res = $this->client->post(self::AUTHORIZE_ENDPOINT, [
                'base_uri' => env('NAFATH_BASE_URL'),
                'headers'  => $this->headers,
                'json'     => [
                    'id'      => $nationalId,
                    'action'  => 'SpRequest',
                    'service' => $service,
                ],
            ]);
        } catch (GuzzleException $e) {
            Log::error('Nafath init error: ' . $e->getMessage());
            return null;
        }

        $data = json_decode($res->getBody(), true);
        return $res->getStatusCode() === 200
            ? $data
            : array_merge($data, ['status' => $data['status'] ?? 'error']);
    }

    /**
     * Check status of Nafath request.
     */
    public function processCallback(string $trans_id, string $nationalId, ?string $phone = null): ?array
    {
        try {
            $payload = [
                'id_number' => $nationalId,
                'phone'     => $nationalId,
            ];

            $res = $this->client->post(self::STATUS_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'apikey 62976ae5-35b3-4e73-8e3e-b0e40d2b2d29',
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Cache-Control' => 'no-cache',
                ],
                'json'    => $payload,
            ]);
        } catch (GuzzleException $e) {
            Log::error('Nafath status check error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'API request failed',
                'error'   => $e->getMessage()
            ];
        }

        $data = json_decode($res->getBody(), true);

        if ($res->getStatusCode() === 200 && isset($data['message']) && $data['message'] === 'Validated') {
            NafathVerification::where('trans_id', $trans_id)
                ->update([
                    'status' => 'approved',
                    'nafath_response' => $data['data'] ?? $data,
                ]);
        }

        return $res->getStatusCode() === 200
            ? ['success' => true, 'data' => $data]
            : ['success' => false, 'message' => 'Non-200 response from Nafath', 'data' => $data ?? []];
    }

    private function registerUser($data) {}
}
