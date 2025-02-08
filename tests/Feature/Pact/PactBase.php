<?php

namespace Tests\Feature\Pact;

use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Standalone\MockService\MockServerConfig;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Config;

abstract class PactBase extends TestCase
{
    const PACT_PATH = __DIR__ . '/../../../storage/app/pacts';
    const ENV_PATH = __DIR__ . '/../../../';

    protected string|null $apiToken = null;
    protected InteractionBuilder $builder;
    protected MockServerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiToken = $this->getApiToken();

        // Create configuration for Pact
        $this->config = new MockServerConfig();
        $this->config
            ->setConsumer('OrderServiceClient') // Same consumer name for all tests
            ->setProvider('OrderManagementAPI') // Same provider name
            ->setPactDir(self::PACT_PATH);

        $this->builder = new InteractionBuilder($this->config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        //Verify interactions after each test
        $this->assertTrue($this->builder->verify());
    }

    protected function getApiToken(): string|null
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(self::ENV_PATH);
        $dotenv->safeLoad();

        return getenv('API_TOKEN') ?: $_ENV['API_TOKEN'] ?? null;
    }



}
