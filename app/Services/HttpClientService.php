<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HttpClientService
{
    private Client $client;
    private string $baseUri;

    public function __construct(string $baseUri)
    {
        $this->baseUri = rtrim($baseUri, '/');
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 10.0, // Set a reasonable timeout
        ]);
    }

    /**
     * Create an order by sending a POST request.
     *
     * @param array $orderData
     * @return array|null
     */
    public function createOrder(array $orderData): ?array
    {
        try {
            $response = $this->client->request('POST', '/order/create', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $orderData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            error_log("HTTP Request failed: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Get an order by sending a GET request.
     *
     * @param string $orderId
     * @return array|null
     */
    public function getOrder(string $orderId): ?array
    {
        try {
            $response = $this->client->request('GET', '/order/view/' . $orderId, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            // Decode the JSON response
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // Log error details
            error_log("HTTP Request failed: " . $e->getMessage());

            // Return null if request fails
            return null;
        }
    }
}
