<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\RestrictionAccountRoutesApi;

class RestrictionAccountService {

  /**
   * The RestrictionAccountRoutesApi client.
   *
   * @var \SymbolRestClient\Api\RestrictionAccountRoutesApi
   */
  protected $restrictionAccountApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->restrictionAccountApi = $api_client_factory->createApi(RestrictionAccountRoutesApi::class);
  }

  /**
   * Get the instance.
   *
   * @return \SymbolRestClient\Api\RestrictionAccountRoutesApi
   */
  public function getRestrictionAccountApi() {
    return $this->restrictionAccountApi;
  }

}