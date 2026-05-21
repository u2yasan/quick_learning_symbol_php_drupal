<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\AccountRoutesApi;
use Exception;

class AccountService {

  /**
   * The AccountRoutesApi client.
   *
   * @var \SymbolRestClient\Api\AccountRoutesApi
   */
  protected $accountApi;

  /**
   * The Symbol API client factory.
   *
   * @var \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory
   */
  protected $apiClientFactory;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->apiClientFactory = $api_client_factory;
    $this->accountApi = $api_client_factory->createApi(AccountRoutesApi::class);
  }

  /**
   * Get the Account instance.
   *
   * @return \SymbolRestClient\Api\AccountRoutesApi
   *   The SymbolFacade instance.
   */
  public function getAccountApi() {
    return $this->accountApi;
  }

  /**
   * Gets account information.
   */
  public function getAccountInfo(string $address, ?string $node_url = NULL): mixed {
    $api = $node_url
      ? $this->apiClientFactory->createApiForNodeUrl(AccountRoutesApi::class, $node_url)
      : $this->accountApi;

    return $api->getAccountInfo($address);
  }

  // /**
  //  * API 呼び出しを安全に実行
  //  *
  //  * @param callable $callback
  //  *   実行する API 関数。
  //  * @param string $method
  //  *   呼び出すメソッド名。
  //  *
  //  * @return mixed|null
  //  *   成功時のレスポンス、または `NULL` (エラー時)。
  //  */
  // protected function safeApiCall(callable $callback, string $method) {
  //   try {
  //     return $callback();
  //   } catch (Exception $e) {
  //     \Drupal::logger('quicklearning_symbol')->error("Error in {$method}: @message", ['@message' => $e->getMessage()]);
  //     return null;
  //   }
  // }
}
