<?php

namespace App\Services;

use GuzzleHttp\Client;

class HttpClientService
{
    private Client $client;

    private string $baseUri;

    public function __construct(string $baseUri)
    {
        $this->baseUri = rtrim($baseUri, '/');
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 10.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Create an order by sending a POST request.
     */
    public function createOrder(array $orderData): array
    {
        $response = $this->client->request('POST', '/order/create', [
            'headers' => [],
            'json' => $orderData,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get an order by sending a GET request.
     */
    public function getOrder(string $orderId): array
    {
        $response = $this->client->request('GET', '/order/view/'.$orderId, [
            'headers' => [],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
