<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\ChainRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;

class ChainService {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var string
   */
  protected $networkType;

  /**
   * @var string
   */
  protected $nodeUrl;

  /**
   * @var \SymbolRestClient\Configuration
   */
  protected $configuration;

  /**
   * @var \SymbolRestClient\Api\ChainRoutesApi
   */
  protected $chainApi;

  /**
   * コンストラクタ
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTPクライアント。
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   設定ファクトリ。
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    
    // 設定を取得
    $this->config = $config_factory->get('quicklearning_symbol.settings');
    $this->networkType = $this->config->get('network_type');

    // ネットワークタイプに応じた URL を取得
    $this->nodeUrl = $this->getNodeUrl($this->networkType);

    // Configuration オブジェクトを作成し、ホストを設定
    $this->configuration = new Configuration();
    $this->configuration->setHost($this->nodeUrl);

    // API クライアントを作成
    $this->chainApi = new ChainRoutesApi($this->httpClient, $this->configuration);
  }

  /**
   * ネットワークタイプに応じたノードURLを取得
   *
   * @param string $networkType
   *   ネットワークタイプ ('testnet' または 'mainnet')。
   *
   * @return string
   *   ノードURL。
   */
  protected function getNodeUrl(string $networkType) {
    $urls = [
      'testnet' => 'http://sym-test-03.opening-line.jp:3000',
      'mainnet' => 'http://sym-main-03.opening-line.jp:3000',
    ];
    return $urls[$networkType] ?? 'http://localhost:3000'; // デフォルトのURL
  }

  /**
   * Get the Chain instance.
   *
   * @return \SymbolRestClient\Api\ChainRoutesApi
   *   The ChainAPI instance.
   */
  public function getChainApi() {
    return $this->chainApi;
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