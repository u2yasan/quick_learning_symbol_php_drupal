<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\MosaicRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Symbol モザイク関連の処理を行うサービスクラス。
 */
class MosaicService {

  /**
   * 設定オブジェクト。
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * ネットワークタイプ。
   *
   * @var string
   */
  protected $networkType;

  /**
   * ノード URL。
   *
   * @var string
   */
  protected $nodeUrl;

  /**
   * API クライアント設定オブジェクト。
   *
   * @var \SymbolRestClient\Configuration
   */
  protected $configuration;

  /**
   * Mosaic API クライアント。
   *
   * @var \SymbolRestClient\Api\MosaicRoutesApi
   */
  protected $mosaicApi;

  /**
   * HTTP クライアント。
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * コンストラクタ。
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTP クライアント。
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   設定ファクトリ。
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    
    // 設定を取得
    $this->config = $config_factory->get('quicklearning_symbol.settings');
    $this->networkType = $this->config->get('network_type') ?? 'testnet';

    // ネットワークタイプに応じたノード URL を取得
    $this->nodeUrl = $this->getNodeUrl($this->networkType);

    // Configuration オブジェクトを作成し、ホストを設定
    $this->configuration = new Configuration();
    $this->configuration->setHost($this->nodeUrl);

    // API クライアントを作成
    $this->mosaicApi = new MosaicRoutesApi($this->httpClient, $this->configuration);
  }

  /**
   * ネットワークタイプに応じたノード URL を取得。
   *
   * @param string $networkType
   *   ネットワークタイプ ('testnet' または 'mainnet')。
   *
   * @return string
   *   ノード URL。
   */
  protected function getNodeUrl(string $networkType): string {
    $urls = [
      'testnet' => 'http://sym-test-03.opening-line.jp:3000',
      'mainnet' => 'http://sym-main-03.opening-line.jp:3000',
    ];
    return $urls[$networkType] ?? 'http://localhost:3000'; // デフォルトの URL
  }

  /**
   * Get the Mosaic instance.
   *
   * @return \SymbolRestClient\Api\MosaicRoutesApi;
   *   The MosaicRoutesApi instance.
   */
  public function getMosaicApi() {
    return $this->mosaicApi;
  }
  // /**
  //  * モザイク情報を取得する。
  //  *
  //  * @param string $mosaic_id
  //  *   モザイク ID。
  //  *
  //  * @return \SymbolRestClient\Model\MosaicInfoDTO|null
  //  *   モザイク情報の DTO、または失敗時に NULL。
  //  */
  // public function getMosaic(string $mosaic_id){
  //   try {
  //     return $this->mosaicApi->getMosaic($mosaic_id);
  //   }
  //   catch (Exception $e) {
  //     \Drupal::logger('quicklearning_symbol')->error("Error in {$method}: @message", ['@message' => $e->getMessage()]);
  //     return NULL;
  //   }
  // }
}