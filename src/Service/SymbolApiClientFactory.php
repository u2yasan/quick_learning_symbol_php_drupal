<?php

namespace Drupal\quicklearning_symbol\Service;

use GuzzleHttp\ClientInterface;
use SymbolRestClient\Api\NetworkRoutesApi;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Configuration;

/**
 * Creates Symbol REST API clients with the configured Drupal HTTP client.
 */
class SymbolApiClientFactory {

  /**
   * Constructs a Symbol API client factory.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Drupal HTTP client.
   * @param \Drupal\quicklearning_symbol\Service\SymbolConfigService $symbolConfig
   *   The Symbol configuration service.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected SymbolConfigService $symbolConfig,
  ) {}

  /**
   * Creates a network routes API client.
   */
  public function createNetworkRoutesApi(): NetworkRoutesApi {
    return new NetworkRoutesApi($this->httpClient, $this->createConfiguration());
  }

  /**
   * Creates a transaction routes API client.
   */
  public function createTransactionRoutesApi(): TransactionRoutesApi {
    return new TransactionRoutesApi($this->httpClient, $this->createConfiguration());
  }

  /**
   * Creates REST client configuration.
   */
  private function createConfiguration(): Configuration {
    $configuration = new Configuration();
    $configuration->setHost($this->symbolConfig->getNodeUrl());
    return $configuration;
  }
}
