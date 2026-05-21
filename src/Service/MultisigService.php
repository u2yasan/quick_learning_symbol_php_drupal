<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\MultisigRoutesApi;

class MultisigService {

  /**
   * The MultisigRoutesApi client.
   *
   * @var \SymbolRestClient\Api\MultisigRoutesApi
   */
  protected $multisigApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->multisigApi = $api_client_factory->createApi(MultisigRoutesApi::class);
  }

  /**
   * Get the Api instance.
   *
   * @return SymbolRestClient\Api\MultisigRoutesApi
   *   The ReceiptRoutesApi instance.
   */
  public function getMultisigApi() {
    return $this->multisigApi;
  }
}