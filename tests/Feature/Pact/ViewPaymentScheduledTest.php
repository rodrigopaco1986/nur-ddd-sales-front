<?php

namespace Tests\Feature\Pact;

use Illuminate\Support\Str;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;

class ViewPaymentScheduledTest extends PactBase
{
    public function test_view_payment_scheduled()
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
            ->setPath('/payment/view-by-order/'.$orderId)
            ->addHeader('Accept', 'application/json');

        $viewResponse = new ProviderResponse;
        $viewResponse->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($this->getInvoiceBody($matcher, $orderId));

        $this->builder
            ->given('An order exists', [
                'order_id' => $orderId,
                'generate_invoice' => 1,
                'payment_installments' => 2,
                'patient_id' => $uuidPatient,
                'uuid_service_1' => $uuidService1,
                'uuid_service_2' => $uuidService2,
            ])
            ->uponReceiving('Get a payment scheduled')
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
        $orderViewResult = $service->getPaymentSchedued($orderId);
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

    private function getInvoiceBody($matcher, $orderId): array
    {
        return [
            'data' => [
                [
                    'payment' => [
                        'id' => $matcher->uuid(),
                        'number' => $matcher->number(1),
                        'amount' => $matcher->number('3'),
                        'due_date' => $matcher->regex(
                            '2024-12-14T11:02:42.000000Z',
                            '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{6}Z$'
                        ),
                        'status' => $matcher->string('PENDING'),
                        'currency' => $matcher->string('BOB'),
                        'order_id' => $matcher->uuid($orderId),
                    ],
                ],
                [
                    'payment' => [
                        'id' => $matcher->uuid(),
                        'number' => $matcher->number(1),
                        'amount' => $matcher->number('3'),
                        'due_date' => $matcher->regex(
                            '2024-12-14T11:02:42.000000Z',
                            '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{6}Z$'
                        ),
                        'status' => $matcher->string('PENDING'),
                        'currency' => $matcher->string('BOB'),
                        'order_id' => $matcher->uuid($orderId),
                    ],
                ],
            ],
        ];
    }
}
