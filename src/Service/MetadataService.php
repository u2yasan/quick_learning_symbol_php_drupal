<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\MetadataRoutesApi;

class MetadataService {

  /**
   * The MetadataRoutesApi client.
   *
   * @var \SymbolRestClient\Api\MetadataRoutesApi
   */
  protected $metadataApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->metadataApi = $api_client_factory->createApi(MetadataRoutesApi::class);
  }

  /**
   * Get the MetadataApi instance.
   *
   * @return \SymbolRestClient\Api\MetadataRoutesApi
   *   The MetadataApi instance.
   */
  public function getMetadataApi() {
    return $this->metadataApi;
  }
  
}