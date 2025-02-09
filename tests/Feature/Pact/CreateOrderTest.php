<?php

namespace Tests\Feature\Pact;

use App\Services\HttpClientService;
use Illuminate\Support\Str;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;

class CreateOrderTest extends PactBase
{
    public function test_create_order(): void
    {
        error_reporting(1);

        [$uuidPatient, $total, $uuidService1, $uuidService2] = [
            Str::uuid(), 1500, Str::uuid(), Str::uuid(),
        ];

        // Create expected request from the consumer.
        $request = new ConsumerRequest;
        $request
            ->setMethod('POST')
            ->setPath('/order/create')
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'patient_id' => $uuidPatient,
                'generate_invoice' => 1,
                'payment_installments' => 2,
                'items' => [
                    [
                        'service_id' => $uuidService1,
                        'quantity' => 1,
                        'price' => 100,
                        'discount' => 0,
                    ],
                    [
                        'service_id' => $uuidService2,
                        'quantity' => 1,
                        'price' => 1500,
                        'discount' => 100,
                    ],
                ],
            ]);

        // Create expected response from the provider.
        $response = new ProviderResponse;
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'data' => [
                    'order' => [
                        'id' => (new Matcher)->uuid(),
                        'patient_id' => (new Matcher)->uuid(),
                        'order_date' => (new Matcher)->regex(
                            '2024-12-14T11:02:42.000000Z', // Example date
                            '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{6}Z$'
                        ),
                        'status' => 'CREATED',
                        'total' => $total,
                        'items' => [
                            [
                                'id' => (new Matcher)->uuid(),
                                'service_id' => $uuidService1,
                                'service_code' => (new Matcher)->string('11111'),
                                'service_name' => (new Matcher)->string('Consulta de catering.'),
                                'service_unit' => (new Matcher)->string('Servicio'),
                                'quantity' => 1,
                                'price' => 100,
                                'discount' => 0,
                                'subtotal' => 100,
                            ],
                            [
                                'id' => (new Matcher)->uuid(),
                                'service_id' => $uuidService2,
                                'service_code' => (new Matcher)->string('11111'),
                                'service_name' => (new Matcher)->string('Consulta de catering.'),
                                'service_unit' => (new Matcher)->string('Servicio'),
                                'quantity' => 1,
                                'price' => 1500,
                                'discount' => 100,
                                'subtotal' => 1400,
                            ],
                        ],
                    ],
                ],
            ]);

        $this->builder
            ->uponReceiving('Create an order')
            ->with($request)
            ->willRespondWith($response);

        // Make the request
        $service = new HttpClientService($this->config->getBaseUri());
        $orderResult = $service->createOrder([
            'patient_id' => $uuidPatient,
            'generate_invoice' => 1,
            'payment_installments' => 2,
            'items' => [
                [
                    'service_id' => $uuidService1,
                    'quantity' => 1,
                    'price' => 100,
                    'discount' => 0,
                ],
                [
                    'service_id' => $uuidService2,
                    'quantity' => 1,
                    'price' => 1500,
                    'discount' => 100,
                ],
            ],
        ]);

        // Assert response is an array
        $this->assertIsArray($orderResult, 'Response is not an array.');

        // Assert 'data' key exists
        $this->assertArrayHasKey('data', $orderResult, "Response does not contain 'data' key.");
        $this->assertIsArray($orderResult['data'], "'data' is not an array.");

        // Assert 'data' has a child key 'order'
        $this->assertArrayHasKey('order', $orderResult['data'], "'data' does not contain 'order' key.");
        $this->assertIsArray($orderResult['data']['order'], "'order' is not an array.");

        // Assert 'order' has an array of 'items'
        $this->assertArrayHasKey('items', $orderResult['data']['order'], "'order' does not contain 'items' key.");
        $this->assertIsArray($orderResult['data']['order']['items'], "'items' is not an array.");

        // Assert 'total' is the same as the one sent
        $this->assertEquals($total, $orderResult['data']['order']['total'], 'Total does not match.');

        // Assert at least one item exists
        $this->assertNotEmpty($orderResult['data']['order']['items'], "'items' array is empty.");

        // Assert first item is same 'service id' than the first item sent
        $this->assertEquals($uuidService1, $orderResult['data']['order']['items'][0]['service_id'], 'First item service id does not match.');

        // Assert second item is same 'service id' than the second item sent
        $this->assertEquals($uuidService2, $orderResult['data']['order']['items'][1]['service_id'], 'First item service id does not match.');
    }
}
