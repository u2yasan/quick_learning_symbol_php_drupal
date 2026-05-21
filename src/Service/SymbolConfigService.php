<?php

namespace Drupal\quicklearning_symbol\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Reads and validates Symbol network configuration.
 */
class SymbolConfigService {

  public const NETWORK_TESTNET = 'testnet';
  public const NETWORK_MAINNET = 'mainnet';

  /**
   * Constructs a Symbol configuration service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Gets the configured network type.
   */
  public function getNetworkType(): string {
    $network_type = $this->configFactory->get('quicklearning_symbol.settings')->get('network_type');
    return in_array($network_type, [self::NETWORK_TESTNET, self::NETWORK_MAINNET], TRUE)
      ? $network_type
      : self::NETWORK_TESTNET;
  }

  /**
   * Gets the configured Symbol node URL for the active network.
   */
  public function getNodeUrl(): string {
    $config = $this->configFactory->get('quicklearning_symbol.settings');
    $network_type = $this->getNetworkType();
    $configured_url = $network_type === self::NETWORK_MAINNET
      ? $config->get('main_node_url')
      : $config->get('test_node_url');

    if (is_string($configured_url) && filter_var($configured_url, FILTER_VALIDATE_URL)) {
      return $configured_url;
    }

    return $network_type === self::NETWORK_MAINNET
      ? 'http://sym-main-03.opening-line.jp:3000'
      : 'http://sym-test-03.opening-line.jp:3000';
  }
}
