<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\SecretLockRoutesApi;
use Exception;

class SecretLockService {

  /**
   * The SecretLockRoutesApi client.
   *
   * @var \SymbolRestClient\Api\SecretLockRoutesApi
   */
  protected $secretLockApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->secretLockApi = $api_client_factory->createApi(SecretLockRoutesApi::class);
  }

  /**
   * Get the SecretLockRoutesApi instance.
   *
   * @return SymbolRestClient\Api\SecretLockRoutesApi
   *   
   */
  public function getSecretLockApi() {
    return $this->secretLockApi;
  }

}