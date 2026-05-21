<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\NodeRoutesApi;
use Exception;

class NodeService {

  /**
   * The NodeRoutesApi client.
   *
   * @var \SymbolRestClient\Api\NodeRoutesApi
   */
  protected $nodeApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->nodeApi = $api_client_factory->createApi(NodeRoutesApi::class);
  }

  
  /**
   * Get the NodeRoutesApi instance.
   *
   * @return SymbolRestClient\Api\NodeRoutesApi
   *   The NodeRoutesApi instance.
   */
  public function getNodeApi() {
    return $this->nodeApi;
  }
  /**
   * アカウント情報を取得
   *
   * @param string $address
   *   アカウントの Symbol アドレス。
   *
   * @return mixed|null
   *   アカウント情報、または `NULL` (エラー時)。
   */
  public function getAccountInfo(string $address) {
    return $this->safeApiCall(function () use ($address) {
      return $this->accountApi->getAccountInfo($address);
    }, 'getAccountInfo');
  }

  /**
   * API 呼び出しを安全に実行
   *
   * @param callable $callback
   *   実行する API 関数。
   * @param string $method
   *   呼び出すメソッド名。
   *
   * @return mixed|null
   *   成功時のレスポンス、または `NULL` (エラー時)。
   */
  protected function safeApiCall(callable $callback, string $method) {
    try {
      return $callback();
    } catch (Exception $e) {
      \Drupal::logger('quicklearning_symbol')->error('Error in @method: @message', [
        '@method' => $method,
        '@message' => $e->getMessage(),
      ]);
      return null;
    }
  }
}