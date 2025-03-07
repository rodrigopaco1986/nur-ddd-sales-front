<?php

namespace Tests\Feature\Pact;

use Illuminate\Support\Str;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;

class ViewOrderTest extends PactBase
{
    public function test_create_and_view_order()
    {
        error_reporting(1);

        [$orderId, $uuidPatient, $uuidService1, $uuidService2, $total] = [
            Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid(), 1500,
        ];

        $matcher = new Matcher;

        $createRequest = new ConsumerRequest;
        $createRequest->setMethod('POST')
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
        $createResponse = new ProviderResponse;
        $createResponse->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($this->getOrderBody($matcher, $orderId, $total, $uuidService1, $uuidService2));

        $this->builder
            ->uponReceiving('Create an order')
            ->with($createRequest)
            ->willRespondWith($createResponse, false); // Don't start the mock server yet

        // Register a new interaction for "View an order".
        $this->builder->newInteraction();

        $viewRequest = new ConsumerRequest;
        $viewRequest->setMethod('GET')
            ->setPath('/order/view/'.$orderId)
            ->addHeader('Accept', 'application/json');

        $viewResponse = new ProviderResponse;
        $viewResponse->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($this->getOrderBody($matcher, $orderId, $total, $uuidService1, $uuidService2));

        $this->builder
            ->given('An order exists', [
                'order_id' => $orderId,
                'generate_invoice' => 1,
                'payment_installments' => 2,
                'patient_id' => $uuidPatient,
                'uuid_service_1' => $uuidService1,
                'uuid_service_2' => $uuidService2,
            ])
            ->uponReceiving('Get an order')
            ->with($viewRequest)
            ->willRespondWith($viewResponse);

        // Now that both interactions are registered, create the service instance.
        $service = new \App\Services\HttpClientService($this->config->getBaseUri());

        // Execute the "Create an order" call.
        $orderCreateResult = $service->createOrder([
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

        // Execute the "Get an order" call.
        $orderViewResult = $service->getOrder($orderId);

        // Order id is the same for both create and view order.
        $this->assertEquals($orderId, $orderCreateResult['data']['order']['id']);
        $this->assertEquals($orderId, $orderViewResult['data']['order']['id']);

        // Assert response is an array
        $this->assertIsArray($orderViewResult, 'Response is not an array.');

        // Assert 'data' key exists
        $this->assertArrayHasKey('data', $orderViewResult, "Response does not contain 'data' key.");
        $this->assertIsArray($orderViewResult['data'], "'data' is not an array.");

        // Assert 'data' has a child key 'order'
        $this->assertArrayHasKey('order', $orderViewResult['data'], "'data' does not contain 'order' key.");
        $this->assertIsArray($orderViewResult['data']['order'], "'order' is not an array.");

        // Assert 'order' has an array of 'items'
        $this->assertArrayHasKey('items', $orderViewResult['data']['order'], "'order' does not contain 'items' key.");
        $this->assertIsArray($orderViewResult['data']['order']['items'], "'items' is not an array.");

        // Assert 'total' is the same as the one sent
        $this->assertEquals($total, $orderViewResult['data']['order']['total'], 'Total does not match.');

        // Assert at least one item exists
        $this->assertNotEmpty($orderViewResult['data']['order']['items'], "'items' array is empty.");

        // Assert first item is same 'service id' than the first item sent
        $this->assertEquals($uuidService1, $orderViewResult['data']['order']['items'][0]['service_id'], 'First item service id does not match.');

        // Assert second item is same 'service id' than the second item sent
        $this->assertEquals($uuidService2, $orderViewResult['data']['order']['items'][1]['service_id'], 'First item service id does not match.');
    }

    private function getOrderBody($matcher, $orderId, $total, $uuidService1, $uuidService2): array
    {
        return [
            'data' => [
                'order' => [
                    // Return the same order id generated above.
                    'id' => $matcher->uuid($orderId),
                    'patient_id' => $matcher->uuid(),
                    'order_date' => $matcher->regex(
                        '2024-12-14T11:02:42.000000Z',
                        '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{6}Z$'
                    ),
                    'status' => 'CREATED',
                    'total' => $total,
                    'items' => [
                        [
                            'id' => $matcher->uuid(),
                            'service_id' => $uuidService1,
                            'service_code' => $matcher->string('11111'),
                            'service_name' => $matcher->string('Consulta de catering.'),
                            'service_unit' => $matcher->string('Servicio'),
                            'quantity' => 1,
                            'price' => 100,
                            'discount' => 0,
                            'subtotal' => 100,
                        ],
                        [
                            'id' => $matcher->uuid(),
                            'service_id' => $uuidService2,
                            'service_code' => $matcher->string('11111'),
                            'service_name' => $matcher->string('Consulta de catering.'),
                            'service_unit' => $matcher->string('Servicio'),
                            'quantity' => 1,
                            'price' => 1500,
                            'discount' => 100,
                            'subtotal' => 1400,
                        ],
                    ],
                ],
            ],
        ];
    }
}
