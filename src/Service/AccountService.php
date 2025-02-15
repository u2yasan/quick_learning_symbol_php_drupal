<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;

class AccountService {

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
   * @var \SymbolRestClient\Api\AccountRoutesApi
   */
  protected $accountApi;

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
    $this->accountApi = new AccountRoutesApi($this->httpClient, $this->configuration);
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
   * Get the Account instance.
   *
   * @return \SymbolRestClient\Api\AccountRoutesApi
   *   The SymbolFacade instance.
   */
  public function getAccountApi() {
    return $this->accountApi;
  }

  // /**
  //  * アカウント情報を取得
  //  *
  //  * @param string $address
  //  *   アカウントの Symbol アドレス。
  //  *
  //  * @return mixed|null
  //  *   アカウント情報、または `NULL` (エラー時)。
  //  */
  // public function getAccountInfo(string $address) {
  //   return $this->safeApiCall(function () use ($address) {
  //     return $this->accountApi->getAccountInfo($address);
  //   }, 'getAccountInfo');
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