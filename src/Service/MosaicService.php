<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\MosaicRoutesApi;

/**
 * Symbol モザイク関連の処理を行うサービスクラス。
 */
class MosaicService {

  /**
   * The MosaicRoutesApi client.
   *
   * @var \SymbolRestClient\Api\MosaicRoutesApi
   */
  protected $mosaicApi;


  /**
   * Constructs the service.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->mosaicApi = $api_client_factory->createApi(MosaicRoutesApi::class);
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