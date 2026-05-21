<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Models\NetworkType;

/**
 * Creates Symbol SDK facade and network model objects.
 */
class SymbolFacadeFactory {

  /**
   * Constructs a Symbol facade factory.
   *
   * @param \Drupal\quicklearning_symbol\Service\SymbolConfigService $symbolConfig
   *   The Symbol configuration service.
   */
  public function __construct(
    protected SymbolConfigService $symbolConfig,
  ) {}

  /**
   * Creates a Symbol facade for the active network.
   */
  public function createFacade(): SymbolFacade {
    return new SymbolFacade($this->symbolConfig->getNetworkType());
  }

  /**
   * Creates a Symbol SDK network type object for the active network.
   */
  public function createNetworkType(): NetworkType {
    return match ($this->symbolConfig->getNetworkType()) {
      SymbolConfigService::NETWORK_MAINNET => new NetworkType(NetworkType::MAINNET),
      default => new NetworkType(NetworkType::TESTNET),
    };
  }
}
