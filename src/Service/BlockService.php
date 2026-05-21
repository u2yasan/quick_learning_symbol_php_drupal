<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\BlockRoutesApi;

class BlockService {

  /**
   * The BlockRoutesApi client.
   *
   * @var \SymbolRestClient\Api\BlockRoutesApi
   */
  protected $blockApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->blockApi = $api_client_factory->createApi(BlockRoutesApi::class);
  }

  /**
   * Get the Chain instance.
   *
   * @return \SymbolRestClient\Api\ChainRoutesApi
   *   The ChainAPI instance.
   */
  public function getBlockApi() {
    return $this->blockApi;
  }

  // /**
  //  * ノード情報を取得
  //  *
  //  * @param string $node_url
  //  *   Node アドレス。
  //  *
  //  * @return mixed|null
  //  *   アカウント情報、または `NULL` (エラー時)。
  //  */
  // public function getChainInfo() {  
  //   return $this->chainApi->getChainInfo();
  // }

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