<?php

namespace Tests\Feature\Pact;

use Illuminate\Support\Str;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;

class ViewInvoiceTest extends PactBase
{
    public function test_create_and_view_invoice()
    {
        error_reporting(1);

        [$orderId, $invoiceId, $uuidPatient, $uuidService1, $uuidService2, $total] = [
            Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid(), Str::uuid(), 1500,
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
            ->setPath('/invoice/view/'.$invoiceId)
            ->addHeader('Accept', 'application/json');

        $viewResponse = new ProviderResponse;
        $viewResponse->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($this->getInvoiceBody($matcher, $invoiceId, $uuidPatient, $total, $uuidService1, $uuidService2));

        $this->builder
            ->given('An order exists with the invoice generated', [
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'patient_id' => $uuidPatient,
                'uuid_service_1' => $uuidService1,
                'uuid_service_2' => $uuidService2,
            ])
            ->uponReceiving('Get an invoice')
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
        $invoiceViewResult = $service->getInvoice($invoiceId);

        // Check order id is the same than provided
        $this->assertEquals($invoiceId, $invoiceViewResult['data']['invoice']['id']);
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

    private function getInvoiceBody($matcher, $invoiceId, $uuidPatient, $total, $uuidService1, $uuidService2): array
    {
        return [
            'data' => [
                'invoice' => [
                    // Return the same invoice id generated above.
                    'id' => $matcher->uuid($invoiceId),
                    'nit' => $matcher->string('171283817238128'),
                    'number' => $matcher->number(2),
                    'authorization_code' => $matcher->string('465A9780DBD5FD71F22F720B938CAF5AE3EB03980654FFCCE54549E74'),
                    'invoice_date' => $matcher->regex(
                        '2024-12-14T11:02:42.000000Z',
                        '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{6}Z$'
                    ),
                    'customer_id' => $uuidPatient,
                    'customer_code' => $matcher->number(1000),
                    'customer_name' => $matcher->string('Janiya Schiller'),
                    'customer_nit' => $matcher->number(7659198),
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
