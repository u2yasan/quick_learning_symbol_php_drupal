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

    if (is_string($configured_url) && self::isValidNodeUrl($configured_url)) {
      return $configured_url;
    }

    return $network_type === self::NETWORK_MAINNET
      ? 'http://sym-main-03.opening-line.jp:3000'
      : 'http://sym-test-03.opening-line.jp:3000';
  }

  /**
   * Validates a configured Symbol node URL.
   */
  public static function isValidNodeUrl(?string $url): bool {
    if (!is_string($url) || $url === '' || preg_match('/[[:cntrl:]]/', $url)) {
      return FALSE;
    }

    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
      return FALSE;
    }

    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], TRUE)) {
      return FALSE;
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
      return FALSE;
    }

    $host = strtolower(trim($parts['host'], '[]'));
    if (in_array($host, ['localhost', 'localhost.localdomain'], TRUE) || str_ends_with($host, '.local')) {
      return FALSE;
    }

    $ip = filter_var($host, FILTER_VALIDATE_IP);
    if ($ip !== FALSE) {
      return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
      ) !== FALSE;
    }

    return (bool) preg_match('/^[a-z0-9.-]+$/', $host);
  }
}
