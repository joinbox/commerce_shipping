<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Event\ShippingEvents;
use Drupal\commerce_shipping\Event\ShippingRatesEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShipmentManager implements ShipmentManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ShipmentManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $rates = [];
    /** @var \Drupal\commerce_shipping\ShippingMethodStorageInterface $shipping_method_storage */
    $shipping_method_storage = $this->entityTypeManager->getStorage('commerce_shipping_method');
    $shipping_methods = $shipping_method_storage->loadMultipleForShipment($shipment);
    foreach ($shipping_methods as $shipping_method) {
      $shipping_method_plugin = $shipping_method->getPlugin();
      $shipping_rates = $shipping_method_plugin->calculateRates($shipment);
      // Allow the rates to be altered via code.
      $event = new ShippingRatesEvent($shipping_rates, $shipping_method, $shipment);
      $this->eventDispatcher->dispatch(ShippingEvents::SHIPPING_RATES, $event);
      $shipping_rates = $event->getRates();

      foreach ($shipping_rates as $shipping_rate) {
        $service = $shipping_rate->getService();
        $rate_id = $shipping_method->id() . '--' . $service->getId();
        $rates[$rate_id] = $shipping_rate;
      }
    }

    return $rates;
  }

}
