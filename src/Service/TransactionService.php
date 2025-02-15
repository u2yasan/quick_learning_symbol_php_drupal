<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Models\NetworkType;
use Drupal\Core\Config\ConfigFactoryInterface;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;

class TransactionService {

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
   * @var \SymbolSdk\Facade\SymbolFacade
   */
  protected $facade;

  /**
   * @var \SymbolSdk\Model\NetworkType
   */
  protected $txNetwork;

  /**
   * @var string
   */
  protected $nodeUrl;

  /**
   * @var \SymbolRestClient\Configuration
   */
  protected $configuration;

  /**
   * @var \SymbolRestClient\Api\TransactionRoutesApi
   */
  protected $transactionApi;

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
    $this->config = $config_factory->get('quicklearning_symbol.settings');
    
    // ネットワークタイプを取得
    $this->networkType = $this->config->get('network_type');
    $this->txNetwork = $this->getNetworkType($this->networkType);

    // SymbolFacade の初期化
    $this->facade = new SymbolFacade($this->networkType);

    // ネットワークURLの設定
    $this->nodeUrl = $this->getNodeUrl($this->networkType);

    // API 設定
    $this->configuration = new Configuration();
    $this->configuration->setHost($this->nodeUrl);

    // API クライアントを作成
    $this->transactionApi = new TransactionRoutesApi($this->httpClient, $this->configuration);
  }

  /**
   * ネットワークタイプに応じたオブジェクトを取得
   *
   * @param string $networkType
   *   ネットワークタイプ ('testnet' または 'mainnet')。
   *
   * @return \SymbolSdk\Symbol\Models\NetworkType;
   *   NetworkType のオブジェクト
   */
  protected function getNetworkType(string $networkType) {
    $types = [
      'testnet' => new NetworkType(NetworkType::TESTNET),
      'mainnet' => new NetworkType(NetworkType::MAINNET),
    ];
    return $types[$networkType] ?? new NetworkType(NetworkType::TESTNET); // デフォルトは TESTNET
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
    return $urls[$networkType] ?? 'http://localhost:3000'; // デフォルトURL
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