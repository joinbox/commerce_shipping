<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Manages shipments.
 */
interface ShipmentManagerInterface {

  /**
   * Calculates rates for the given shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The rates.
   */
  public function calculateRates(ShipmentInterface $shipment);

}
