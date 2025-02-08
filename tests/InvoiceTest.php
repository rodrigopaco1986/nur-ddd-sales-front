<?php

namespace Tests\Feature\Pact;

use Tests\Feature\Pact\PactBaseTest;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use Illuminate\Support\Str;

class InvoiceTest extends PactBaseTest
{
    /*public function testCreateOrder(): void
    {
        // Create expected response from the provider.
        list($uuidPatient, $total, $uuidOrder, $uuidCustomer, $uuidItem1, $uuidService1, $uuidItem2, $uuidService2) = [
            Str::uuid(), 1500, Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid()
        ];

        // Create expected request from the consumer.
        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/order/create')
            ->addHeader('Content-Type', 'application/json');

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                "data" => [
                    "order" => [
                        "id" => $uuidOrder,
                        "customer_id" => $uuidCustomer,
                        "order_date" => "2024-12-11T03:01:02.000000Z",
                        "status" => "COMPLETED",
                        "total" => $total,
                        "items" => [
                            [
                                "id" => $uuidItem1,
                                "service_id" => $uuidService1,
                                "service_code" => "11111",
                                "service_name" => "Consulta de catering.",
                                "service_unit" => "Servicio",
                                "quantity" => 1,
                                "price" => 100,
                                "discount" => 0,
                                "subtotal" => 100
                            ],
                            [
                                "id" => $uuidItem2,
                                "service_id" => $uuidService2,
                                "service_code" => "22222",
                                "service_name" => "Catering mensual.",
                                "service_unit" => "Servicio",
                                "quantity" => 1,
                                "price" => 1500,
                                "discount" => 100,
                                "subtotal" => 1400
                            ]
                        ]
                    ]
                ]
            ]);

        $this->builder->uponReceiving('Create an order')->with($request)->willRespondWith($response);

        $service = new \App\Services\HttpClientService($this->config->getBaseUri());
        $orderResult = $service->createOrder([
            "patient_id" => $uuidPatient,
            "generate_invoice" => 1,
            "payment_installments" => 2,
            "items" => [
                [
                    "service_id" => $uuidService1,
                    "quantity" => 1,
                    "price" => 100,
                    "discount" => 0
                ],
                [
                    "service_id" => $uuidService2,
                    "quantity" => 1,
                    "price" => 1500,
                    "discount" => 100
                ]
            ]
        ]);

        //Assert response is an array
        $this->assertIsArray($orderResult, "Response is not an array.");
        
        //Assert 'data' key exists
        $this->assertArrayHasKey('data', $orderResult, "Response does not contain 'data' key.");
        $this->assertIsArray($orderResult['data'], "'data' is not an array.");

        //Assert 'data' has a child key 'order'
        $this->assertArrayHasKey('order', $orderResult['data'], "'data' does not contain 'order' key.");
        $this->assertIsArray($orderResult['data']['order'], "'order' is not an array.");

        //Assert 'order' has an array of 'items'
        $this->assertArrayHasKey('items', $orderResult['data']['order'], "'order' does not contain 'items' key.");
        $this->assertIsArray($orderResult['data']['order']['items'], "'items' is not an array.");

        //Assert 'order' has an key 'id'
        if (!empty($orderResult['data']['order']['id'])) {
            $this->assertArrayHasKey('id', $orderResult['data']['order'], "Order does not contain 'id' key.");
        }

        //Assert 'total' is the same as the one sent
        $this->assertEquals($total, $orderResult['data']['order']['total'], "Total does not match.");

        //Assert at least one item exists
        $this->assertNotEmpty($orderResult['data']['order']['items'], "'items' array is empty.");

        //Assert first item contains 'id' key if items exist
        if (!empty($orderResult['data']['order']['items'])) {
            $this->assertArrayHasKey('id', $orderResult['data']['order']['items'][0], "First item does not contain 'id' key.");
        }

        //Assert first item is same 'service id' than the first item sent
        $this->assertEquals($uuidService1, $orderResult['data']['order']['items'][0]['service_id'], "First item service id does not match.");

        //Assert second item is same 'service id' than the second item sent
        $this->assertEquals($uuidService2, $orderResult['data']['order']['items'][1]['service_id'], "First item service id does not match.");
    }*/
}