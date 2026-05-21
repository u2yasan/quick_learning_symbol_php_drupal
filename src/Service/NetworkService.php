<?php

namespace Drupal\quicklearning_symbol\Service;

class NetworkService {

  /**
   * @var \SymbolRestClient\Api\NetworkRoutesApi
   */
  protected $networkApi;

  /**
   * コンストラクタ
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->networkApi = $api_client_factory->createNetworkRoutesApi();
  }

  /**
   * Get the NetworkRoutesApi instance.
   *
   * @return \SymbolRestClient\Api\NetworkRoutesApi
   *   The NetworkRoutesApi instance.
   */
  public function getNetworkRoutesApi() {
    return $this->networkApi;
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
