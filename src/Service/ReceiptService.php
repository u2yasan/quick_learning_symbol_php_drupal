<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\ReceiptRoutesApi;
use SymbolRestClient\Configuration;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;

class ReceiptService {

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
   * @var \SymbolRestClient\Api\ReciptRoutesApi
   */
  protected $receiptApi;

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
    $this->receiptApi = new ReceiptRoutesApi($this->httpClient, $this->configuration);
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
   * Get the ReceiptRoutesApi instance.
   *
   * @return SymbolRestClient\Api\ReceiptRoutesApi
   *   The ReceiptRoutesApi instance.
   */
  public function getReceiptApi() {
    return $this->receiptApi;
  }


  // /**
  //    * Operation searchAddressResolutionStatements
  //    *
  //    * Get receipts address resolution statements
  //    *
  //    * @param  string $height Filter by block height. (optional)
  //    * @param  int $page_size Select the number of entries to return. (optional, default to 10)
  //    * @param  int $page_number Filter by page number. (optional, default to 1)
  //    * @param  string $offset Entry id at which to start pagination. If the ordering parameter is set to -id, the elements returned precede the identifier. Otherwise, newer elements with respect to the id are returned. (optional)
  //    * @param  Order $order Sort responses in ascending or descending order based on the collection property set on the param &#x60;&#x60;orderBy&#x60;&#x60;. If the request does not specify &#x60;&#x60;orderBy&#x60;&#x60;, REST returns the collection ordered by id. (optional)
  //    * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['searchAddressResolutionStatements'] to see the possible values for this operation
  //    *
  //    * @throws \SymbolRestClient\ApiException on non-2xx response or if the response body is not in the expected format
  //    * @throws \InvalidArgumentException
  //    * @return \SymbolRestClient\Model\ResolutionStatementPage|\SymbolRestClient\Model\ModelError|\SymbolRestClient\Model\ModelError
  //    */
  //   public function searchAddressResolutionStatements($height)
  //   {
  //     return $this->safeApiCall(function () use ($height) {
  //       return $this->receiptApi->searchAddressResolutionStatements($height);
  //     }, 'searchAddressResolutionStatements');
  //   }

  //   /**
  //    * Operation searchMosaicResolutionStatements
  //    *
  //    * Get receipts mosaic resolution statements
  //    *
  //    * @param  string $height Filter by block height. (optional)
  //    * @param  int $page_size Select the number of entries to return. (optional, default to 10)
  //    * @param  int $page_number Filter by page number. (optional, default to 1)
  //    * @param  string $offset Entry id at which to start pagination. If the ordering parameter is set to -id, the elements returned precede the identifier. Otherwise, newer elements with respect to the id are returned. (optional)
  //    * @param  Order $order Sort responses in ascending or descending order based on the collection property set on the param &#x60;&#x60;orderBy&#x60;&#x60;. If the request does not specify &#x60;&#x60;orderBy&#x60;&#x60;, REST returns the collection ordered by id. (optional)
  //    * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['searchMosaicResolutionStatements'] to see the possible values for this operation
  //    *
  //    * @throws \SymbolRestClient\ApiException on non-2xx response or if the response body is not in the expected format
  //    * @throws \InvalidArgumentException
  //    * @return \SymbolRestClient\Model\ResolutionStatementPage|\SymbolRestClient\Model\ModelError|\SymbolRestClient\Model\ModelError
  //    */
  //   public function searchMosaicResolutionStatements($height)
  //   {
  //     return $this->safeApiCall(function () use ($height) {
  //       return $this->receiptApi->searchMosaicResolutionStatements($height);
  //     }, 'searchMosaicResolutionStatements');
  //   }

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