<?php

namespace Drupal\quicklearning_symbol\Service;

class TransactionService {

  /**
   * @var \SymbolSdk\Facade\SymbolFacade
   */
  protected $facade;

  /**
   * @var \SymbolSdk\Model\NetworkType
   */
  protected $txNetwork;

  /**
   * @var \SymbolRestClient\Api\TransactionRoutesApi
   */
  protected $transactionApi;

  /**
   * コンストラクタ
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   * @param \Drupal\quicklearning_symbol\Service\SymbolFacadeFactory $facade_factory
   *   The Symbol facade factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory, SymbolFacadeFactory $facade_factory) {
    $this->facade = $facade_factory->createFacade();
    $this->txNetwork = $facade_factory->createNetworkType();
    $this->transactionApi = $api_client_factory->createTransactionRoutesApi();
  }

  /**
   * Get the TransactionRoutesApi instance.
   *
   * @return \SymbolRestClient\Api\TransactionRoutesApi;
   *   The TransactionRoutesApi instance.
   */
  public function getTransactionApi() {
    return $this->transactionApi;
  }

}
