<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\TransactionStatusRoutesApi;

class TransactionStatusService {

  /**
   * The TransactionStatusRoutesApi client.
   *
   * @var \SymbolRestClient\Api\TransactionStatusRoutesApi
   */
  protected $transactionStatusApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->transactionStatusApi = $api_client_factory->createApi(TransactionStatusRoutesApi::class);
  }

  /**
   * Get the TransactionStatusRoutesApi instance.
   *
   * @return \SymbolRestClient\Api\TransactionStatusRoutesApi;
   *   The TransactionStatusRoutesApi instance.
   */
  public function getTransactionStatusApi() {
    return $this->transactionStatusApi;
  }
}
