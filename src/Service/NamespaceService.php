<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\NamespaceRoutesApi;
use Exception;

/**
 * Symbol namespace関連の処理を行うサービスクラス。
 */
class NamespaceService {

  /**
   * The NamespaceRoutesApi client.
   *
   * @var \SymbolRestClient\Api\NamespaceRoutesApi
   */
  protected $namespaceApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->namespaceApi = $api_client_factory->createApi(NamespaceRoutesApi::class);
  }

   /**
   * Get the Mosaic instance.
   *
   * @return SymbolRestClient\Api\NamespaceRoutesApi
   *   The MosaicRoutesApi instance.
   */
  public function getNamespaceApi() {
    return $this->namespaceApi;
  }
  
  /**
   * ネームスペース情報を取得する。
   *
   * @param string $namespace_id
   *   Namespace ID。
   *
   * @return \SymbolRestClient\Model\NamespaceInfoDTO|null
   *   Namaspace情報の DTO、または失敗時に NULL。
   */
  // public function getNamespace(string $namespace_id){
  //   try {
  //     return $this->namespaceApi->getMosaic($mosaic_id);
  //   }
  //   catch (Exception $e) {
  //     \Drupal::logger('quicklearning_symbol')->error("Error in {$method}: @message", ['@message' => $e->getMessage()]);
  //     return NULL;
  //   }
  // }

  public function getNamespace(string $namespace_id){
    return $this->safeApiCall(function () use ($namespace_id) {
      return $this->namespaceApi->getNamespace($namespace_id, 'application/json');
    }, 'getNamespace');
  }

  /**
   * Operation searchNamespaces
   *
   * Search namespaces
   *
   * @param  string $owner_address Filter by owner address. (optional)
   * @param  NamespaceRegistrationTypeEnum $registration_type Filter by registration type. (optional)
   * @param  string $level0 Filter by root namespace. (optional)
   * @param  AliasTypeEnum $alias_type Filter by alias type. (optional)
   * @param  int $page_size Select the number of entries to return. (optional, default to 10)
   * @param  int $page_number Filter by page number. (optional, default to 1)
   * @param  string $offset Entry id at which to start pagination. If the ordering parameter is set to -id, the elements returned precede the identifier. Otherwise, newer elements with respect to the id are returned. (optional)
   * @param  Order $order Sort responses in ascending or descending order based on the collection property set on the param &#x60;&#x60;orderBy&#x60;&#x60;. If the request does not specify &#x60;&#x60;orderBy&#x60;&#x60;, REST returns the collection ordered by id. (optional)
   * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['searchNamespaces'] to see the possible values for this operation
   *
   * @throws \SymbolRestClient\ApiException on non-2xx response or if the response body is not in the expected format
   * @throws \InvalidArgumentException
   * @return \SymbolRestClient\Model\NamespacePage|\SymbolRestClient\Model\ModelError
   */
    public function searchNamespaces($owner_address = null, $registration_type = null, $level0 = null, $alias_type = null, $page_size = 10, $page_number = 1, $offset = null, $order = null, string $contentType = 'application/json')
    {
        list($response) = $this->searchNamespacesWithHttpInfo($owner_address, $registration_type, $level0, $alias_type, $page_size, $page_number, $offset, $order, $contentType);
        return $response;
    }
  // public function searchNamespaces($owner_address = null, $registration_type = null, $level0 = null, $alias_type = null, $page_size = 10, $page_number = 1, $offset = null, $order = null, string $contentType = self::contentTypes['searchNamespaces'][0])
  // public function searchNamespaces(string $owner_address){
  //   return $this->safeApiCall(function () use ($owner_address) {
  //     return $this->namespaceApi->searchNamespaces($owner_address, null, null, null, 10, 1, null, null, 'application/json');
  //   }, 'searchRootNamespaces');
  // }


  /**
     * Operation getNamespacesNames
     *
     * Get readable names for a set of namespaces
     *
     * @param  \SymbolRestClient\Model\NamespaceIds $namespace_ids namespace_ids (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getNamespacesNames'] to see the possible values for this operation
     *
     * @throws \SymbolRestClient\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \SymbolRestClient\Model\NamespaceNameDTO[]|\SymbolRestClient\Model\ModelError|\SymbolRestClient\Model\ModelError
     */
    // public function getNamespacesNames($namespace_ids, string $contentType = self::contentTypes['getNamespacesNames'][0])
    // {
    //     list($response) = $this->getNamespacesNamesWithHttpInfo($namespace_ids, $contentType);
    //     return $response;
    // }
    public function getNamespacesNames($namespace_ids){
      return $this->safeApiCall(function () use ($namespace_ids) {
        return $this->namespaceApi->getNamespacesNames($namespace_ids, 'application/json');
      }, 'getNamespacesNames');
    }

    public function getAccountsNames($addresses){
      return $this->safeApiCall(function () use ($addresses) {
        return $this->namespaceApi->getAccountsNames($addresses, 'application/json');
      }, 'getAccountsNames');
    }

    public function getMosaicsNames($mosaicIds){
      return $this->safeApiCall(function () use ($mosaicIds) {
        return $this->namespaceApi->getMosaicsNames($mosaicIds, 'application/json');
      }, 'getMosaicsNames');
    }

   /**
   * 共通の API 呼び出しラッパー
   *
   * @param callable $callback
   *   実行する API 関数。
   * @param string $method
   *   メソッド名。
   *
   * @return mixed
   *
   * @throws \Exception
   */
  protected function safeApiCall(callable $callback, string $method) {
    try {
      return $callback();
    } catch (Exception $e) {
      \Drupal::logger('quicklearning_symbol')->error('Error in @method: @message', [
        '@method' => $method,
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }
}