<?php

namespace Tests\Feature\Pact;

use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Standalone\MockService\MockServerConfig;
use PHPUnit\Framework\TestCase;

abstract class PactBase extends TestCase
{
    const PACT_PATH = __DIR__.'/../../../storage/app/pacts';

    protected InteractionBuilder $builder;

    protected MockServerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Create configuration for Pact
        $this->config = new MockServerConfig;
        $this->config
            ->setConsumer('OrderServiceClient') // Same consumer name for all tests
            ->setProvider('OrderManagementAPI') // Same provider name
            ->setPactDir(self::PACT_PATH);

        $this->builder = new InteractionBuilder($this->config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Verify interactions after each test
        $this->assertTrue($this->builder->verify());
    }
}
