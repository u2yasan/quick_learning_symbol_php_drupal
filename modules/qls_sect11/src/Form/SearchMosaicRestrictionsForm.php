<?php

namespace Drupal\qls_sect11\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Facade\SymbolFacade;

use SymbolRestClient\Configuration;
use SymbolRestClient\Api\RestrictionMosaicRoutesApi;
use SymbolSdk\Symbol\Models\NetworkType;

use Drupal\quicklearning_symbol\Service\RestrictionMosaicService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class SearchMosaicRestrictionsForm extends FormBase {

   /**
   * The RestrictionMosaicService instance.
   *
   * @var \Drupal\quicklearing_symbol\Service\RestrictionMosaicService
   */
  protected $restrictionMosaicService;

  /**
   * 
   * @param \Drupal\quicklearing_symbol\Service\RestrictionMosaicService $restriction_mosaic_service
   *   The account service.
   */
  public function __construct(RestrictionMosaicService $restriction_mosaic_service) {
    $this->restrictionMosaicService = $restriction_mosaic_service;
  }

  public static function create(ContainerInterface $container) {
    // AccountService をコンストラクタで注入
    $form = new static(
        $container->get('quicklearning_symbol.restriction_mosaic_service')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_mosaic_restrictions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '11.2.3 '. $this->t('制限状態確認'),
    ];

    $form['mosaic_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $network_type = $form_state->getValue('network_type');
    // $facade = new SymbolFacade($network_type);
    // // ノードURLを設定
    // if ($network_type === 'testnet') {
    //   $networkType = new NetworkType(NetworkType::TESTNET);
    //   $node_url = 'http://sym-test-03.opening-line.jp:3000';
    // } elseif ($network_type === 'mainnet') {
    //   $networkType = new NetworkType(NetworkType::MAINNET);
    //   $node_url = 'http://sym-main-03.opening-line.jp:3000';
    // }
    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();
    // $restrictionAipInstance = new RestrictionMosaicRoutesApi($client, $config);
    // $mosaic_id = "0x".$form_state->getValue('mosaic_id');
    // $mosaicID = new UnresolvedMosaicId($mosaic_id);
    $restrictionMosaicApi = $this->restrictionMosaicService->getRestrictionMosaicApi();

    $mosaic_id = $form_state->getValue('mosaic_id'); 
    
    // \Drupal::logger('qls_sect11')->info('jsonPayload: @jsonPayload', ['@jsonPayload' => print_r($jsonPayload, TRUE)]);
    try {
      $result = $restrictionMosaicApi->searchMosaicRestrictions(
        mosaic_id: $mosaic_id
      ); 
      $formattedResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      $this->messenger()->addMessage($this->t('Mosaic Restrictions: <pre>@result</pre>', ['@result' => $formattedResult]));  
    } catch (Exception $e) {
      \Drupal::logger('qls_sect11')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    } 
  }
}