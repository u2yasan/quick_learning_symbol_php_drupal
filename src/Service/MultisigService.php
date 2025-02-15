<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\MultisigRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class MultisigService {

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
   * @var \SymbolRestClient\Api\MultisigRoutesApi
   */
  protected $multisigApi;

  /**
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
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
    $this->multisigApi = new MultisigRoutesApi($this->httpClient, $this->configuration);
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
   * Get the Api instance.
   *
   * @return SymbolRestClient\Api\MultisigRoutesApi
   *   The ReceiptRoutesApi instance.
   */
  public function getMultisigApi() {
    return $this->multisigApi;
  }
}