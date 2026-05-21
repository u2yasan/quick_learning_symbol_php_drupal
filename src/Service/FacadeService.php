<?php
namespace Drupal\quicklearning_symbol\Service;

/**
 * Service for managing Symbol Facade.
 */
class FacadeService {

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
   * @param \Drupal\quicklearning_symbol\Service\SymbolFacadeFactory $facade_factory
   *   The Symbol facade factory.
   */
  public function __construct(SymbolFacadeFactory $facade_factory) {
    $this->facade = $facade_factory->createFacade();
    $this->networkTypeObject = $facade_factory->createNetworkType();
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

}
