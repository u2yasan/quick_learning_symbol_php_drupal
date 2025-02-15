<?php
namespace Drupal\quicklearning_symbol\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Models\NetworkType;

/**
 * Service for managing Symbol Facade.
 */
class FacadeService {

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
   * @var \SymbolSdk\Symbol\Models\NetworkType
   */
  protected $networkTypeObject;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    // Load configuration.
    $this->config = $config_factory->get('quicklearning_symbol.settings');
    $this->networkType = $this->config->get('network_type');

    // Initialize SymbolFacade.
    $this->facade = new SymbolFacade($this->networkType);

    // Convert network type to correct format.
    $this->networkTypeObject = $this->getNetworkType($this->networkType);
  }

  /**
   * Get the SymbolFacade instance.
   *
   * @return \SymbolSdk\Facade\SymbolFacade
   *   The SymbolFacade instance.
   */
  public function getFacade() {
    return $this->facade;
  }

  public function getNetworkTypeObject() {
    return $this->networkTypeObject;
  }

  /**
   * Convert network type string to NetworkType object.
   *
   * @param string $networkType
   *   'testnet' or 'mainnet'.
   *
   * @return \SymbolSdk\Symbol\Models\NetworkType
   *   The NetworkType object.
   */
  protected function getNetworkType(string $networkType) {
    $types = [
      'testnet' => new NetworkType(NetworkType::TESTNET),
      'mainnet' => new NetworkType(NetworkType::MAINNET),
    ];
    return $types[$networkType] ?? new NetworkType(NetworkType::TESTNET); // Default to TESTNET
  }
}