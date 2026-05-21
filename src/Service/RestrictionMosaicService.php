<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\RestrictionMosaicRoutesApi;

class RestrictionMosaicService {

  /**
   * The RestrictionMosaicRoutesApi client.
   *
   * @var \SymbolRestClient\Api\RestrictionMosaicRoutesApi
   */
  protected $restrictionMosaicApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->restrictionMosaicApi = $api_client_factory->createApi(RestrictionMosaicRoutesApi::class);
  }

  /**
   * Get the instance.
   * 
   * @return \SymbolRestClient\Api\RestrictionMosaicRoutesApi
   */
  public function getRestrictionMosaicApi() {
    return $this->restrictionMosaicApi;
  }

}