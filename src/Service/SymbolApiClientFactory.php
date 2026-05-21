<?php

namespace Drupal\quicklearning_symbol\Service;

use Drupal\Core\Http\ClientFactory;
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
    protected ClientFactory $httpClientFactory,
    protected SymbolConfigService $symbolConfig,
  ) {}

  /**
   * Creates a network routes API client.
   */
  public function createNetworkRoutesApi(): NetworkRoutesApi {
    return new NetworkRoutesApi($this->createHttpClient(), $this->createConfiguration());
  }

  /**
   * Creates a transaction routes API client.
   */
  public function createTransactionRoutesApi(): TransactionRoutesApi {
    return new TransactionRoutesApi($this->createHttpClient(), $this->createConfiguration());
  }

  /**
   * Creates any generated Symbol REST API client.
   *
   * @template T of object
   *
   * @param class-string<T> $api_class
   *   The generated API client class.
   *
   * @return T
   *   The generated API client.
   */
  public function createApi(string $api_class): object {
    return new $api_class($this->createHttpClient(), $this->createConfiguration());
  }

  /**
   * Creates any generated Symbol REST API client for an explicit node URL.
   *
   * @template T of object
   *
   * @param class-string<T> $api_class
   *   The generated API client class.
   * @param string $node_url
   *   The validated Symbol node URL.
   *
   * @return T
   *   The generated API client.
   */
  public function createApiForNodeUrl(string $api_class, string $node_url): object {
    if (!$this->symbolConfig->isValidNodeUrl($node_url)) {
      throw new \InvalidArgumentException('Invalid Symbol node URL.');
    }
    return new $api_class($this->createHttpClient(), $this->createConfiguration($node_url));
  }

  /**
   * Creates REST client configuration.
   */
  private function createConfiguration(?string $node_url = NULL): Configuration {
    $configuration = new Configuration();
    $configuration->setHost($node_url ?? $this->symbolConfig->getNodeUrl());
    return $configuration;
  }

  /**
   * Creates an HTTP client with a bounded timeout for external node calls.
   */
  private function createHttpClient(): object {
    return $this->httpClientFactory->fromOptions([
      'timeout' => 10,
      'connect_timeout' => 5,
    ]);
  }
}
